<?php

namespace App\Support;

/**
 * Number-to-words in the Tanzanian shilling style.
 *
 * Cash receipts in Tanzania read large numbers in three-digit groups joined by
 * "na": 2,853,643 is read as "milioni mbili na mia nane hamsini tatu na mia siti
 * na thelathini tatu" rather than as one long unit name.
 */
class Money
{
    /** @var array<int, string> */
    private const UNITS = [1 => 'moja', 'mbili', 'tatu', 'nne', 'tano', 'sita', 'saba', 'nane', 'tisa'];

    /** @var array<int, string> Index 10..19. */
    private const TEENS = [
        10 => 'kumi',
        11 => 'kumi na moja',
        12 => 'kumi na mbili',
        13 => 'kumi na tatu',
        14 => 'kumi na nne',
        15 => 'kumi na tano',
        16 => 'kumi na sita',
        17 => 'kumi na saba',
        18 => 'kumi na nane',
        19 => 'kumi na tisa',
    ];

    /** @var array<int, string> Round tens, index 20..90. */
    private const TENS = [
        20 => 'ishirini',
        30 => 'thelathini',
        40 => 'arobaini',
        50 => 'hamsini',
        60 => 'sitini',
        70 => 'sabini',
        80 => 'themini',
        90 => 'nini',
    ];

    /** @var array<int, string> Scale names, smallest first. */
    private const SCALES = [
        [1_000_000_000, 'bilioni'],
        [1_000_000, 'milioni'],
        [1_000, 'elfu'],
    ];

    /**
     * The amount in words, e.g. "mia mbili na tano".
     *
     * Shillings are the only unit: the schema stores a single decimal amount
     * with no minor-unit column, so cents are deliberately not supported.
     */
    public static function inWords(float|int|string $amount): string
    {
        $units = (int) round(abs((float) $amount));

        if ($units === 0) {
            return 'sifuri';
        }

        $words = [];
        $remaining = $units;

        foreach (self::SCALES as [$scale, $name]) {
            if ($remaining < $scale) {
                continue;
            }

            $count = intdiv($remaining, $scale);
            $remaining -= $count * $scale;

            if ($count > 0) {
                $words[] = self::withScale($count, $name);
            }
        }

        if ($remaining > 0) {
            $words[] = self::underThousand($remaining);
        }

        return implode(' na ', array_filter($words));
    }

    /**
     * Attach a scale name to a count.
     *
     * Tanzanian usage puts the scale first for small counts ("elfu moja",
     * "milioni mbili") but counts of hundreds first ("mia moja elfu",
     * "mia moja milioni").
     */
    private static function withScale(int $count, string $scale): string
    {
        return $count < 100
            ? $scale.' '.self::underThousand($count)
            : self::underThousand($count).' '.$scale;
    }

    /**
     * Words for 1..999 only.
     */
    private static function underThousand(int $n): string
    {
        $parts = [];

        $hundreds = intdiv($n, 100);
        $rest = $n % 100;

        if ($hundreds > 0) {
            $parts[] = 'mia '.self::UNITS[$hundreds];
        }

        if ($rest >= 10 && $rest <= 19) {
            $parts[] = self::TEENS[$rest];
        } elseif ($rest > 0) {
            $tens = intdiv($rest, 10) * 10;

            if (isset(self::TENS[$tens])) {
                $parts[] = self::TENS[$tens];
            }

            if ($rest % 10 > 0) {
                $parts[] = self::UNITS[$rest % 10];
            }
        }

        return implode(' na ', $parts);
    }
}
