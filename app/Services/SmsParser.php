<?php

namespace App\Services;

use App\Models\Network;
use Illuminate\Support\Carbon;

/**
 * Parses Tanzanian mobile-money SMS messages into the fields the system
 * stores on a transaction.
 */
class SmsParser
{
    /**
     * @return array{reference: string, type: string, amount: float, customer_name: string, customer_phone: string, balance: float, received_at: string|null}
     */
    public function parse(string $body): array
    {
        foreach (config('sms.templates', []) as $template) {
            if (! preg_match($template['pattern'], $body, $m)) {
                continue;
            }

            return [
                'reference' => strtoupper(trim($m['ref'])),
                'type' => $template['type'],
                'amount' => (float) str_replace(',', '', $m['amount']),
                'customer_name' => trim(preg_replace('/\s+/', ' ', $m['customer'] ?? '')),
                'customer_phone' => $m['phone'] ?? '',
                'balance' => isset($m['balance']) ? (float) str_replace(',', '', $m['balance']) : 0.0,
                'received_at' => $this->parseDateTime($m['date'] ?? null, $m['time'] ?? null),
            ];
        }

        return [
            'reference' => '',
            'type' => '',
            'amount' => 0.0,
            'customer_name' => '',
            'customer_phone' => '',
            'balance' => 0.0,
            'received_at' => null,
        ];
    }

    /**
     * Resolve the network an SMS belongs to from its sender name.
     */
    public function identifyNetwork(string $sender, ?Network $fallback): ?Network
    {
        foreach (config('sms.senders', []) as $keyword => $code) {
            if (stripos($sender, $keyword) !== false) {
                return Network::firstWhere('code', $code) ?? $fallback;
            }
        }

        return $fallback;
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

        return Carbon::createFromFormat(
            'Y-m-d H:i',
            $year.'-'.$parts[1].'-'.$parts[0].' '.($time ?? '00:00')
        )->format('Y-m-d H:i:s');
    }
}
