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
     * The authenticator app is set up and its secret can still be read.
     */
    public static function appReady(User $user): bool
    {
        if (! $user->two_factor_app_enabled) {
            return false;
        }

        try {
            Crypt::decryptString((string) $user->two_factor_secret);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Explain why a user still has to set up two-factor authentication.
     *
     * Returns null once a usable method exists, 'missing' when two-factor was
     * never set up, or 'misconfigured' when it is enabled but no method can
     * deliver a verification code.
     */
    public static function setupIssue(User $user, SmsSender $sender): ?string
    {
        if (! $user->two_factor_enabled) {
            return 'missing';
        }

        if (self::appReady($user)) {
            return null;
        }

        return self::verifiedPhone($user, $sender) !== null ? null : 'misconfigured';
    }

    public static function needsSetup(User $user, SmsSender $sender): bool
    {
        return self::setupIssue($user, $sender) !== null;
    }

    /**
     * @return array<int, string>
     */
    public static function available(User $user, SmsSender $sender): array
    {
        if (! $user->two_factor_enabled) {
            return [];
        }

        $methods = [];

        if (self::appReady($user)) {
            $methods[] = 'app';
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
