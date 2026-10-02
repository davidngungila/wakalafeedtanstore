<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class Shift
{
    public const MORNING = 'morning';

    public const NIGHT = 'night';

    public const FULL = 'full';

    /**
     * The business day / shift a timestamp falls in.
     *
     * Morning shift: 08:00–19:59:59 on the same calendar date.
     * Night shift:   20:00–07:59:59, anchored to the calendar date it started.
     *
     * @return array{date: string, shift: string, start: Carbon, end: Carbon}
     */
    public static function for(CarbonInterface $at): array
    {
        $at = Carbon::instance($at);

        if ($at->hour >= 8 && $at->hour < 20) {
            return [
                'date' => $at->toDateString(),
                'shift' => self::MORNING,
                'start' => $at->copy()->setTime(8, 0, 0),
                'end' => $at->copy()->setTime(19, 59, 59),
            ];
        }

        if ($at->hour >= 20) {
            return [
                'date' => $at->toDateString(),
                'shift' => self::NIGHT,
                'start' => $at->copy()->setTime(20, 0, 0),
                'end' => $at->copy()->addDay()->setTime(7, 59, 59),
            ];
        }

        return [
            'date' => $at->copy()->subDay()->toDateString(),
            'shift' => self::NIGHT,
            'start' => $at->copy()->subDay()->setTime(20, 0, 0),
            'end' => $at->copy()->setTime(7, 59, 59),
        ];
    }

    /**
     * The active shift right now.
     *
     * @return array{date: string, shift: string}
     */
    public static function current(): array
    {
        $current = self::for(now());

        return ['date' => $current['date'], 'shift' => $current['shift']];
    }

    /**
     * Window covered by a business-date reconciliation.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function window(string $date, string $shift): array
    {
        $start = Carbon::parse($date)->setTime(8, 0, 0);

        return match ($shift) {
            self::MORNING => [$start, Carbon::parse($date)->setTime(19, 59, 59)],
            self::NIGHT => [Carbon::parse($date)->setTime(20, 0, 0), Carbon::parse($date)->addDay()->setTime(7, 59, 59)],
            default => [$start, Carbon::parse($date)->addDay()->setTime(7, 59, 59)],
        };
    }

    public static function label(string $shift): string
    {
        return match ($shift) {
            self::MORNING => 'Morning (08:00–19:59)',
            self::NIGHT => 'Night (20:00–07:59)',
            default => 'Full day (08:00–07:59+)',
        };
    }
}
