<?php

namespace App\Support;

/**
 * RFC 6238 time-based one-time passwords over RFC 4648 base32 secrets.
 * Dependency-free implementation used for two-factor authentication.
 */
final class TwoFactor
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const RECOVERY_CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    /**
     * @return array<int, string>
     */
    public static function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $chunks = [];

            for ($c = 0; $c < 4; $c++) {
                $chunk = '';

                for ($j = 0; $j < 4; $j++) {
                    $chunk .= self::RECOVERY_CHARS[random_int(0, strlen(self::RECOVERY_CHARS) - 1)];
                }

                $chunks[] = $chunk;
            }

            $codes[] = implode('-', $chunks);
        }

        return $codes;
    }

    public static function hashRecoveryCode(string $code): string
    {
        return hash('sha256', strtoupper(trim($code)));
    }

    public static function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $code = trim($code);

        if ($code === '' || preg_match('/^[0-9]{6}$/', $code) !== 1) {
            return false;
        }

        $decoded = self::base32Decode($secret);

        if ($decoded === '') {
            return false;
        }

        $counter = intdiv(time(), 30);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::generate($decoded, $counter + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    public static function otpauthUri(string $secret, string $account, string $issuer = 'Wakala Platform'): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer).':'.rawurlencode($account)
            .'?secret='.rawurlencode($secret)
            .'&issuer='.rawurlencode($issuer)
            .'&algorithm=SHA1&digits=6&period=30';
    }

    public static function base32Encode(string $data): string
    {
        $binary = '';

        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($binary, 5) as $chunk) {
            $encoded .= self::ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    public static function base32Decode(string $data): string
    {
        $data = strtoupper(preg_replace('/[^A-Z2-7]/', '', $data));

        if ($data === '') {
            return '';
        }

        $binary = '';

        foreach (str_split($data) as $char) {
            $binary .= str_pad(decbin((int) strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        $decoded = '';

        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) < 8) {
                break;
            }

            $decoded .= chr(bindec($chunk));
        }

        return $decoded;
    }

    private static function generate(string $secret, int $counter): string
    {
        $hash = hash_hmac('sha1', pack('J', $counter), $secret, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = (unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF) % 1000000;

        return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
    }
}
