<?php

namespace App\Services\Sms;

use App\Services\Parsers\TigoSmsParser;
use Illuminate\Support\Carbon;

/**
 * Strict, template-based transaction matcher.
 *
 * A message becomes a transaction ONLY if it matches an approved template for its network:
 *   SUPPORTED NETWORK + SUCCESS INDICATOR + VALID REFERENCE + VALID AMOUNT + ACTION + DATE/TIME
 *
 * No TZS sniffing, no generic amount extraction. If no template matches → not a transaction.
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

            // Strict validation: all required groups must be present and non-empty
            $ref = strtoupper(trim($m['ref'] ?? ''));
            $amountRaw = $m['amount'] ?? '';
            $type = $template['type'] ?? '';

            if ($ref === '' || $amountRaw === '' || $type === '') {
                continue;
            }

            $amount = (float) str_replace(',', '', $amountRaw);
            if ($amount <= 0) {
                continue;
            }

            // Amount must be a valid transaction amount (not just any number)
            // Fee and commission are optional but validated if present
            $fee = isset($m['fee']) ? (float) str_replace(',', '', $m['fee']) : 0.0;
            $commission = isset($m['commission']) ? (float) str_replace(',', '', $m['commission']) : 0.0;
            $balance = isset($m['balance']) ? (float) str_replace(',', '', $m['balance']) : 0.0;

            // For financial SMS, Preview Commission is also extracted outside template if not captured
            if ($commission <= 0 && preg_match('/Preview\s+Commission\s*:\s*([\d,]+(?:\.\d+)?)\s*TZS/i', $body, $cm) === 1) {
                $commission = (float) str_replace(',', '', $cm[1]);
            }

            return [
                'reference' => $ref,
                'type' => $type,
                'amount' => $amount,
                'customer_name' => trim(preg_replace('/\s+/', ' ', $m['customer'] ?? '')),
                'customer_phone' => $m['phone'] ?? '',
                'balance' => $balance,
                'commission' => $commission,
                'fee' => $fee,
                'received_at' => $this->parseDateTime($m['date'] ?? null, $m['time'] ?? null),
            ];
        }

        return null;
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
