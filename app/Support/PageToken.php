<?php

namespace App\Support;

/**
 * Opaque page tokens for paginated URLs.
 *
 * A paginated link carries an encrypted token instead of a plain page number
 * (e.g. /audit?page=UHFnZToxOToz...fQ), so page numbers are never exposed in
 * the URL. Tokens are resolved back to integers by NormalizePageQuery before
 * the request reaches a controller, so controllers keep using ordinary paging.
 *
 * The token uses the same HMAC + base64url scheme as the other encrypted ids
 * in this application (see Reconciliation::getRouteKey), which keeps the
 * token short, URL safe and deterministic per page.
 */
class PageToken
{
    public const PARAM = 'page';

    /**
     * Encrypt a page number for use in a URL.
     */
    public static function encode(int $page): string
    {
        $payload = 'page|'.max(1, $page);
        $sig = hash_hmac('sha256', $payload, (string) config('app.key'));

        return rtrim(strtr(base64_encode($payload.':'.$sig), '+/', '-_'), '=');
    }

    /**
     * Determine whether the value is an opaque token rather than a plain number.
     */
    public static function isToken(mixed $value): bool
    {
        return is_string($value) && $value !== '' && ! ctype_digit($value);
    }

    /**
     * Resolve a page value that may be either a plain number or a token.
     *
     * @return int|null The page number, or null when the value is neither a
     *                  number nor a token this application issued.
     */
    public static function decode(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return max(1, (int) $value);
        }

        if (! is_string($value)) {
            return null;
        }

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

        if (! hash_equals($expected, $sig) || ! preg_match('/^page\|(\d+)$/', $payload, $matches)) {
            return null;
        }

        return max(1, (int) $matches[1]);
    }
}
