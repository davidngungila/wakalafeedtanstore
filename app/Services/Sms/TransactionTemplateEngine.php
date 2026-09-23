<?php

namespace App\Services\Sms;

use App\Services\Parsers\TigoSmsParser;
use Illuminate\Support\Carbon;

/**
 * Template-based transaction matcher with universal fallback.
 *
 * Pipeline for each message:
 *   1. Provider-specific strict templates (highest confidence)
 *   2. GenericParser's universal templates (broad, network-agnostic)
 *      via `match($body, null)` / default parser
 *
 * Each template must yield: non-empty reference + amount > 0 + type.
 * Reference falls back to a `ref2` capture if `ref` is empty (useful when
 * the TID: label appears after the body rather than at the start).
 */
class TransactionTemplateEngine
{
    /**
     * Try to match body against approved templates for the given provider.
     *
     * @return array{reference: string, type: string, amount: float, customer_name: string, customer_phone: string, balance: float, commission: float, fee: float, received_at: string|null}|null
     */
    public function match(string $body, ?string $provider = null): ?array
    {
        $parser = $this->parserFor($provider);

        foreach ($parser->templates() as $template) {
            if (preg_match($template['pattern'], $body, $m) !== 1) {
                continue;
            }

            $ref = strtoupper(trim($m['ref'] ?? ''));
            if ($ref === '' && isset($m['ref2'])) {
                $ref = strtoupper(trim($m['ref2']));
            }
            $amountRaw = $m['amount'] ?? '';
            $type = $template['type'] ?? '';

            if ($ref === '' || $amountRaw === '' || $type === '') {
                continue;
            }

            $amount = (float) str_replace(',', '', $amountRaw);
            if ($amount <= 0) {
                continue;
            }

            $fee = isset($m['fee']) ? (float) str_replace(',', '', $m['fee']) : 0.0;
            $commission = isset($m['commission']) ? (float) str_replace(',', '', $m['commission']) : 0.0;
            $balance = isset($m['balance']) ? (float) str_replace(',', '', $m['balance']) : 0.0;

            if ($commission <= 0) {
                $commission = $this->fallbackCommission($body);
            }

            $phone = $m['phone'] ?? '';
            if ($phone !== '' && strlen($phone) === 9 && ctype_digit($phone)) {
                $phone = '0'.$phone;
            }

            return [
                'reference' => $ref,
                'type' => $type,
                'amount' => $amount,
                'customer_name' => trim(preg_replace('/\s+/', ' ', $m['customer'] ?? '')),
                'customer_phone' => $phone,
                'balance' => $balance,
                'commission' => $commission,
                'fee' => $fee,
                'received_at' => $this->parseDateTime($m['date'] ?? null, $m['time'] ?? null),
            ];
        }

        return null;
    }

    private function fallbackCommission(string $body): float
    {
        $patterns = [
            '/Preview\s+Commission\s*:\s*([\d,]+(?:\.\d+)?)\s*TZS/i',
            '/commission\s+(?:before\s+Tax\s+)?is\s+([\d,]+(?:\.\d+)?)\s*T[Ss][Hh]/i',
            '/\b(?:commission|ada|malipo)\s*[:=]\s*T[Ss][Hh]\s*([\d,]+(?:\.\d+)?)/i',
            '/\b(?:commission|ada|malipo)\s*[:=]\s*([\d,]+(?:\.\d+)?)\s*T[Ss][Hh]/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $cm) === 1) {
                return (float) str_replace(',', '', $cm[1]);
            }
        }

        return 0.0;
    }

    private function parserFor(?string $provider): object
    {
        return match ($provider) {
            'mpesa' => new Parsers\MpesaParser,
            'airtel' => new Parsers\AirtelMoneyParser,
            'mixx' => new Parsers\MixxParser,
            'halopesa' => new Parsers\HaloPesaParser,
            'tigo' => app(TigoSmsParser::class) ?? new Parsers\TigoParser,
            'ttcl' => new Parsers\TtclPesaParser,
            'azam' => new Parsers\AzamPesaParser,
            'bank' => new Parsers\BankParser,
            default => new Parsers\GenericParser,
        };
    }

    private function parseDateTime(?string $date, ?string $time): ?string
    {
        if ($date === null) {
            return null;
        }

        $parts = explode('/', str_replace('-', '/', $date));
        if (count($parts) !== 3) {
            return null;
        }

        $year = strlen($parts[2]) === 4 ? $parts[2] : '20'.$parts[2];
        $time = $time ?? '00:00';
        $hasSeconds = substr_count($time, ':') === 2;
        $format = $hasSeconds ? 'Y-m-d H:i:s' : 'Y-m-d H:i';

        try {
            return Carbon::createFromFormat($format, $year.'-'.$parts[1].'-'.$parts[0].' '.$time)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            try {
                return Carbon::parse($year.'-'.$parts[1].'-'.$parts[0].' '.$time)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return null;
            }
        }
    }
}
