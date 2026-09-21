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

            $result = [
                'reference' => strtoupper(trim($m['ref'] ?? '')),
                'type' => $template['type'],
                'amount' => (float) str_replace(',', '', $m['amount'] ?? 0),
                'customer_name' => trim(preg_replace('/\s+/', ' ', $m['customer'] ?? '')),
                'customer_phone' => $m['phone'] ?? '',
                'balance' => isset($m['balance']) ? (float) str_replace(',', '', $m['balance']) : 0.0,
                'commission' => isset($m['commission']) ? (float) str_replace(',', '', $m['commission']) : 0.0,
                'received_at' => $this->parseDateTime($m['date'] ?? null, $m['time'] ?? null),
            ];

            // Some providers include Preview Commission outside the main template (e.g. HaloPesa)
            if ($result['commission'] <= 0 && preg_match('/Preview\s+Commission\s*:\s*([\d,]+(?:\.\d+)?)\s*TZS/i', $body, $cm) === 1) {
                $result['commission'] = (float) str_replace(',', '', $cm[1]);
            }

            return $result;
        }

        return $this->blank();
    }

    /**
     * @return array<int, array{type: string, pattern: string}>
     */
    abstract protected function providerTemplates(): array;

    /**
     * The behaviour each provider builds on. Named captures shared by every
     * template:
     *
     *   ref       leading confirmation code
     *   amount    monetary amount
     *   customer  counterparty name
     *   phone     counterparty number (where available)
     *   date      "22/6/26" or "22-06-2026"
     *   time      "14:21" or "14:21:30"
     *   balance   resulting wallet balance (optional)
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
