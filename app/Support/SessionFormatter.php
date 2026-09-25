<?php

namespace App\Support;

use Illuminate\Support\Carbon;

final class SessionFormatter
{
    public static function format(object $row, bool $isCurrent): array
    {
        return [
            'id' => $row->id,
            'ip' => $row->ip_address ?: 'Unknown',
            'device' => self::parseDevice($row->user_agent),
            'browser' => self::parseBrowser($row->user_agent),
            'ua' => $row->user_agent,
            'last_seen' => Carbon::createFromTimestamp($row->last_activity),
            'is_current' => $isCurrent,
        ];
    }

    private static function parseDevice(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown device';
        }

        if (preg_match('/Windows/', $userAgent)) {
            return 'Windows';
        }

        if (preg_match('/Macintosh|Mac OS X/', $userAgent)) {
            return 'macOS';
        }

        if (preg_match('/iPhone/', $userAgent)) {
            return 'iPhone';
        }

        if (preg_match('/iPad/', $userAgent)) {
            return 'iPad';
        }

        if (preg_match('/Android/', $userAgent)) {
            return 'Android';
        }

        if (preg_match('/Linux/', $userAgent)) {
            return 'Linux';
        }

        return 'Browser';
    }

    private static function parseBrowser(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Unknown browser';
        }

        if (preg_match('/Edg\//', $userAgent)) {
            return 'Microsoft Edge';
        }

        if (preg_match('/OPR\//', $userAgent)) {
            return 'Opera';
        }

        if (preg_match('/SamsungBrowser/', $userAgent)) {
            return 'Samsung Internet';
        }

        if (preg_match('/CriOS\//', $userAgent)) {
            return 'Chrome (iOS)';
        }

        if (preg_match('/Chrome\//', $userAgent)) {
            return 'Chrome';
        }

        if (preg_match('/Firefox\/|FxiOS\//', $userAgent)) {
            return 'Firefox';
        }

        if (preg_match('/Safari\//', $userAgent) && ! preg_match('/Android/', $userAgent)) {
            return 'Safari';
        }

        return 'Browser';
    }
}
