<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'device_uid',
    'name',
    'model',
    'agent_id',
    'network_id',
    'phone_number',
    'sim_number',
    'android_version',
    'app_version',
    'api_token_hash',
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
     * @return HasMany<SmsMessage, $this>
     */
    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    /**
     * Generate a fresh API token, returning the plain-text secret and its hash.
     *
     * @return array{plain: string, hash: string}
     */
    public static function makeApiToken(): array
    {
        $plain = 'dv_'.Str::random(48);

        return ['plain' => $plain, 'hash' => hash('sha256', $plain)];
    }

    public function setApiToken(string $plain): void
    {
        $this->api_token_hash = hash('sha256', $plain);
    }

    public function hasApiToken(string $plain): bool
    {
        return $this->api_token_hash !== null
            && hash_equals($this->api_token_hash, hash('sha256', $plain));
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
        $this->api_token_hash = null;
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
}
