<?php

namespace App\Services\Parsers;

use Illuminate\Support\Carbon;

/**
 * Shared behaviour for every provider parser: the capture groups expected by
 * all Tanzanian mobile-money SMS formats and how a match becomes the stored
 * transaction fields.
 *
 * Each provider may add its own templates on top of the common financial ones,
 * so a changed or unusual provider format is fixed inside one parser instead
 * of breaking the rest of the pipeline.
 */
abstract class AbstractSmsParser implements SmsParserContract
{
    /**
     * @return array<int, array{type: string, pattern: string}>
     */
    public function templates(): array
    {
        return array_merge($this->baseTemplates(), $this->providerTemplates());
    }

    public function parse(string $body): array
    {
        foreach ($this->templates() as $template) {
            if (preg_match($template['pattern'], $body, $m) !== 1) {
                continue;
            }

            $ref = strtoupper(trim($m['ref'] ?? ''));
            if ($ref === '' && isset($m['ref2'])) {
                $ref = strtoupper(trim($m['ref2']));
            }

            $phone = $m['phone'] ?? '';
            if ($phone !== '' && strlen($phone) === 9 && ctype_digit($phone)) {
                $phone = '0'.$phone;
            }

            $commission = isset($m['commission']) ? (float) str_replace(',', '', $m['commission']) : 0.0;
            if ($commission <= 0) {
                $commission = $this->fallbackCommission($body);
            }

            $result = [
                'reference' => $ref,
                'type' => $template['type'],
                'amount' => (float) str_replace(',', '', $m['amount'] ?? 0),
                'customer_name' => trim(preg_replace('/\s+/', ' ', $m['customer'] ?? '')),
                'customer_phone' => $phone,
                'balance' => isset($m['balance']) ? (float) str_replace(',', '', $m['balance']) : 0.0,
                'commission' => $commission,
                'received_at' => $this->parseDateTime($m['date'] ?? null, $m['time'] ?? null),
            ];

            if ($result['reference'] === '' || $result['amount'] <= 0) {
                continue;
            }

            return $result;
        }

        return $this->blank();
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

    /**
     * @return array<int, array{type: string, pattern: string}>
     */
    abstract protected function providerTemplates(): array;

    /**
     * The behaviour each provider builds on. Named captures shared by every
     * template:
     *
     *   ref       leading confirmation code (or trailing TID via ref2)
     *   ref2      fallback reference when TID: appears at end of message
     *   amount    monetary amount
     *   customer  counterparty name
     *   phone     counterparty number (where available)
     *   date      "22/6/26" or "22-06-2026"
     *   time      "14:21" or "14:21:30"
     *   balance   resulting wallet balance (optional)
     *   commission charge/commission when explicitly mentioned (optional)
     *
     * @return array<int, array{type: string, pattern: string}>
     */
    protected function baseTemplates(): array
    {
        return [
            'deposit' => [
                'type' => 'deposit',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[:\s]+.*?(?:deposited|credite?d).+?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).+?from\s+(?P<customer>.+?)\s+(?P<phone>0\d{8,9})\b.*?(?:on\s+(?P<date>\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}))?\s*(?:at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?))?.*?(?:balance is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
            'received' => [
                'type' => 'withdrawal',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[:\s]+.*?received.*?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).*?from\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b.*?(?:on\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}))?\s*(?:at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?))?.*?(?:balance is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
            'withdrawal' => [
                'type' => 'withdrawal',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[:\s]+.*?(?:withdrawn|cash out|cashout).+?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).+?from\s+(?P<customer>.+?)\s+(?P<phone>0\d{8,9})\b.*?(?:on\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}))?\s*(?:at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?))?.*?(?:balance is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
            'send_money' => [
                'type' => 'send_money',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[:\s]+.*?(?:sent|transferred).*?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).+?to\s+(?P<customer>[^\n]+?)\s+(?P<phone>0\d{8,9})\b.*?(?:on\s+(?P<date>\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}))?\s*(?:at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?))?.*?(?:balance is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
            'bill_payment' => [
                'type' => 'bill_payment',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[:\s]+.*?(?:paid|payment to|bill\s+pyt).*?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).+?to\s+(?P<customer>[^\n]+?)\s*.*?(?:on\s+(?P<date>\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}))?\s*(?:at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?))?.*?(?:balance is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
            'airtime' => [
                'type' => 'airtime',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[:\s]+.*?(?:airtime).*?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).+?to\s+(?P<customer>[^\n]+?)\s*.*?(?:on\s+(?P<date>\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}))?\s*(?:at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?))?.*?(?:balance is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
            'send_money_tid_trailing' => [
                'type' => 'send_money',
                'pattern' => '/(?:Sent|Transferred|Umetuma|Paid|Umelipa)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]\s+(?:to|kwenda)\s+(?P<customer>[A-Z][A-Za-z\'\s.]{1,59}?)\s+(?P<phone>0?\d{8,10})\b.*?Balance\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]?.*?commission\s+(?:before\s+Tax\s+)?is\s+(?P<commission>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]?.*?(?:TID|TXN|TNX|Trans?\.?\s*ID)\s*[:#]?\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]{4,})/isu',
            ],
            'received_tid_trailing' => [
                'type' => 'withdrawal',
                'pattern' => '/(?:Received|Deposited|Umepokea|Umeweka|Credited)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]\s+(?:from|kwa)\s+(?P<customer>[A-Z][A-Za-z\'\s.]{1,59}?)\s+(?P<phone>0?\d{8,10})\b.*?Balance\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]?.*?commission\s+(?:before\s+Tax\s+)?is\s+(?P<commission>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]?.*?(?:TID|TXN|TNX|Trans?\.?\s*ID)\s*[:#]?\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]{4,})/isu',
            ],
            'swahili_imefanikiwa' => [
                'type' => 'withdrawal',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,16})[\s:]+.*?IMEFANIKIWA.*?T[Ss][Hh]\s+(?P<amount>[\d,]+(?:\.\d+)?).*?(?:kwa|from)\s+(?P<customer>[A-Za-z][A-Za-z\'\s.]{1,59}?)\s*[\s.(]+(?P<phone>0?\d{9,10})\b.*?(?:tarehe|on)\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?(?:Salio\s+jipya|Balance)\s*[:]?\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]/isu',
            ],
            'float_bank_transfer' => [
                'type' => 'bank_to_wallet',
                'pattern' => '/(?:You\s+Received|Received|Umewekwa|Umepokea)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]\s+(?:from|kwa)\s+(?P<phone>\d{6,15})\s*,?\s*(?P<customer>[^\.]{1,60}?)\s*\.?\s*(?:New\s+balance|Salio\s+jipya)\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]?\.?\s*(?:Trans?\.?\s*ID|TID)\s*[:#]?\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]*)/isu',
            ],
        ];
    }

    /**
     * @return array{reference: string, type: string, amount: float, customer_name: string, customer_phone: string, balance: float, commission: float, received_at: null}
     */
    private function blank(): array
    {
        return [
            'reference' => '',
            'type' => '',
            'amount' => 0.0,
            'customer_name' => '',
            'customer_phone' => '',
            'balance' => 0.0,
            'commission' => 0.0,
            'received_at' => null,
        ];
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

        // Normalize seconds: support both H:i and H:i:s
        $hasSeconds = substr_count($time, ':') === 2;
        $format = $hasSeconds ? 'Y-m-d H:i:s' : 'Y-m-d H:i';

        try {
            return Carbon::createFromFormat(
                $format,
                $year.'-'.$parts[1].'-'.$parts[0].' '.$time
            )->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            try {
                return Carbon::parse($year.'-'.$parts[1].'-'.$parts[0].' '.$time)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return null;
            }
        }
    }
}
