<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

#[Fillable([
    'device_uid',
    'device_code',
    'name',
    'model',
    'agent_id',
    'network_id',
    'phone_number',
    'sim_number',
    'android_version',
    'app_version',
    'authorization_token_hash',
    'encrypted_token',
    'branch',
    'status',
    'last_ip',
    'last_sms_at',
    'last_sync_at',
    'last_heartbeat_at',
    'activated_at',
    'suspended_at',
    'blocked_at',
    'revoked_at',
])]
class Device extends Model
{
    public const MANAGED_STATUSES = ['pending', 'active', 'suspended', 'blocked', 'revoked'];

    private const DEVICE_CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected function casts(): array
    {
        return [
            'last_sms_at' => 'datetime',
            'last_sync_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'activated_at' => 'datetime',
            'suspended_at' => 'datetime',
            'blocked_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Agent, $this>
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * @return BelongsTo<Network, $this>
     */
    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    /**
     * All networks this device is allowed to ingest SMS for and whose
     * transactions it can access (many-to-many).
     *
     * @return BelongsToMany<Network, $this>
     */
    public function networks(): BelongsToMany
    {
        return $this->belongsToMany(Network::class)->withTimestamps();
    }

    /**
     * @return HasMany<SmsMessage, $this>
     */
    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    /**
     * Generate a unique 6-character device code using an unambiguous alphabet
     * (no O/0, I/1, l).
     */
    public static function generateDeviceCode(): string
    {
        $alphabet = self::DEVICE_CODE_ALPHABET;
        $length = strlen($alphabet);

        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, $length - 1)];
            }
        } while (static::where('device_code', $code)->exists());

        return $code;
    }

    /**
     * Generate a fresh authorization token, returning the plain-text secret and its hash.
     *
     * @return array{plain: string, hash: string}
     */
    public static function makeAuthorizationToken(): array
    {
        $plain = bin2hex(random_bytes(32));

        return ['plain' => $plain, 'hash' => hash('sha256', $plain)];
    }

    /**
     * Generate both device code and authorization token for a new device.
     *
     * @return array{device_code: string, token_plain: string, token_hash: string}
     */
    public static function generateCredentials(): array
    {
        $deviceCode = static::generateDeviceCode();
        ['plain' => $tokenPlain, 'hash' => $tokenHash] = static::makeAuthorizationToken();

        return [
            'device_code' => $deviceCode,
            'token_plain' => $tokenPlain,
            'token_hash' => $tokenHash,
        ];
    }

    public function setAuthorizationToken(string $plain): void
    {
        $this->authorization_token_hash = hash('sha256', $plain);
        $this->encrypted_token = Crypt::encryptString($plain);
    }

    /**
     * Get the decrypted authorization token.
     */
    public function getDecryptedToken(): ?string
    {
        if ($this->encrypted_token === null) {
            return null;
        }

        try {
            return Crypt::decryptString($this->encrypted_token);
        } catch (DecryptException $e) {
            return null;
        }
    }

    public function hasAuthorizationToken(string $plain): bool
    {
        return $this->authorization_token_hash !== null
            && hash_equals($this->authorization_token_hash, hash('sha256', $plain));
    }

    public function approve(): void
    {
        $this->status = 'active';
        $this->activated_at = now();
        $this->suspended_at = null;
        $this->blocked_at = null;
        $this->revoked_at = null;
        $this->save();
    }

    public function suspend(): void
    {
        $this->status = 'suspended';
        $this->suspended_at = now();
        $this->save();
    }

    public function block(): void
    {
        $this->status = 'blocked';
        $this->blocked_at = now();
        $this->save();
    }

    public function revoke(): void
    {
        $this->status = 'revoked';
        $this->revoked_at = now();
        $this->authorization_token_hash = null;
        $this->encrypted_token = null;
        $this->save();
    }

    /**
     * A device that is active but has not checked in recently is offline.
     */
    public function isOffline(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $threshold = now()->subMinutes((int) config('sms.offline_after_minutes', 10));

        return $this->last_heartbeat_at === null || $this->last_heartbeat_at->lt($threshold);
    }

    /**
     * Display label for the served badge.
     */
    public function displayedStatus(): string
    {
        return $this->isOffline() ? 'offline' : $this->status;
    }

    public function todayCounts(): array
    {
        return [
            'received' => $this->smsMessages()->whereDate('server_received_at', today())->count(),
            'processed' => $this->smsMessages()->whereDate('server_received_at', today())->where('processing_status', 'processed')->count(),
            'pending' => $this->smsMessages()->whereDate('server_received_at', today())->where('processing_status', 'received')->count(),
            'failed' => $this->smsMessages()->whereDate('server_received_at', today())->where('processing_status', 'failed')->count(),
            'duplicate' => $this->smsMessages()->whereDate('server_received_at', today())->where('is_duplicate', true)->count(),
        ];
    }

    /**
     * Networks the device is assigned to via the pivot, falling back to the
     * legacy primary network for devices registered before the pivot existed.
     *
     * @return Collection<int, Network>
     */
    public function assignedNetworks(): Collection
    {
        $networks = $this->networks()->get();

        if ($networks->isEmpty() && $this->network_id !== null) {
            $primary = $this->network()->first();

            if ($primary !== null) {
                $networks->push($primary);
            }
        }

        return $networks;
    }

    /**
     * @return array<int, string>
     */
    public function networkCodes(): array
    {
        return $this->assignedNetworks()->pluck('code')->filter()->values()->all();
    }
}
