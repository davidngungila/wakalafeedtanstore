<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'action',
    'entity_type',
    'entity_id',
    'details',
    'ip_address',
])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Opaque token for the audited entity so raw database ids are never
     * shown or carried in links. Uses the same HMAC scheme as the other
     * models' route keys (entity type + id signed with the app key).
     */
    public function getEncryptedEntityIdAttribute(): string
    {
        if ($this->entity_id === null) {
            return '';
        }

        $payload = ($this->entity_type ?: 'entity').'|'.(int) $this->entity_id;

        return $this->encodeReference($payload);
    }

    /**
     * Opaque token for the audit entry itself.
     */
    public function getEncryptedIdAttribute(): string
    {
        if (! $this->exists || $this->getKey() === null) {
            return '';
        }

        return $this->encodeReference('audit|'.(int) $this->getKey());
    }

    /**
     * Decode a token produced by this model back to its raw payload, or null
     * when the token is invalid or was signed with a different key.
     */
    public static function decodeReference(string $value): ?string
    {
        $padded = strtr($value, '-_', '+/');
        $padLen = strlen($padded) % 4;

        if ($padLen) {
            $padded .= str_repeat('=', 4 - $padLen);
        }

        $decoded = base64_decode($padded, true);

        if ($decoded === false || ! str_contains($decoded, ':')) {
            return null;
        }

        [$payload, $sig] = explode(':', $decoded, 2);
        $expected = hash_hmac('sha256', $payload, (string) config('app.key'));

        return hash_equals($expected, $sig) ? $payload : null;
    }

    private function encodeReference(string $payload): string
    {
        $sig = hash_hmac('sha256', $payload, (string) config('app.key'));

        return rtrim(strtr(base64_encode($payload.':'.$sig), '+/', '-_'), '=');
    }
}
