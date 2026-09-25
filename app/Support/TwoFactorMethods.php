<?php

namespace App\Support;

use App\Models\User;
use App\Services\SmsSender;
use Illuminate\Support\Facades\Crypt;
use InvalidArgumentException;
use Throwable;

final class TwoFactorMethods
{
    /**
     * @return array<int, string>
     */
    public static function available(User $user, SmsSender $sender): array
    {
        if (! $user->two_factor_enabled) {
            return [];
        }

        $methods = [];

        if ($user->two_factor_app_enabled) {
            try {
                Crypt::decryptString((string) $user->two_factor_secret);
                $methods[] = 'app';
            } catch (Throwable) {
            }
        }

        if (self::verifiedPhone($user, $sender) !== null) {
            $methods[] = 'sms';
        }

        return $methods;
    }

    /**
     * @param  array<int, string>  $methods
     */
    public static function default(array $methods, ?string $preferred): ?string
    {
        if ($preferred !== null && in_array($preferred, $methods, true)) {
            return $preferred;
        }

        if (in_array('sms', $methods, true)) {
            return 'sms';
        }

        return $methods[0] ?? null;
    }

    public static function verifiedPhone(User $user, SmsSender $sender): ?string
    {
        if ($user->phone_verified_at === null || ! $sender->isConfigured()) {
            return null;
        }

        try {
            return $sender->normalizeRecipient((string) $user->phone);
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
