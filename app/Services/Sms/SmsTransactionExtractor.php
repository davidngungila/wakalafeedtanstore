<?php

namespace App\Services\Sms;

use Illuminate\Support\Carbon;

/**
 * SMS → Transaction extractor with smart fallback.
 *
 * Usage:
 *   $result = $extractor->extract($body, $provider, $sender, $networkCode);
 *   if ($result === null) => not a transaction (promo/OTP/balance-only/etc. -> NEEDS_REVIEW)
 *   else => valid transaction fields
 *
 * Pipeline (in order of confidence):
 *   1. Pre-filter: reject OTP / promo / balance-only announcements
 *   2. Provider-specific strict templates (highest confidence)
 *   3. Fallback to universal GenericParser templates for known + unknown networks
 *   4. Last-resort SmartHeuristicParser for completely unseen formats
 *
 * Known provider list still matters for routing to strict templates, but ANY
 * sender (including new networks never configured) can now yield a valid
 * transaction as long as the universal/smart parsers extract a reference,
 * amount, and action type.
 */
class SmsTransactionExtractor
{
    private TransactionTemplateEngine $engine;

    private Parsers\SmartHeuristicParser $heuristic;

    public function __construct(?TransactionTemplateEngine $engine = null, ?Parsers\SmartHeuristicParser $heuristic = null)
    {
        $this->engine = $engine ?? new TransactionTemplateEngine;
        $this->heuristic = $heuristic ?? new Parsers\SmartHeuristicParser;
    }

    /**
     * @return array{reference: string, type: string, amount: float, customer_name: string, customer_phone: string, balance: float, commission: float, fee: float, received_at: string|null}|null
     */
    public function extract(string $body, ?string $provider, ?string $sender = null, ?string $networkCode = null): ?array
    {
        if ($this->isRejected($body, $sender)) {
            return null;
        }

        $commissionNotice = $this->extractCommissionNotice($body);

        if ($commissionNotice !== null) {
            return $commissionNotice;
        }

        $matched = $this->engine->match($body, $provider);

        if ($matched === null) {
            $matched = $this->engine->match($body, null);
        }

        if ($matched !== null && $matched['reference'] !== '' && $matched['amount'] > 0) {
            return $matched;
        }

        $heuristic = $this->heuristic->extract($body);
        if ($heuristic !== null && $heuristic['reference'] !== '' && $heuristic['amount'] > 0 && $heuristic['type'] !== '') {
            return array_merge([
                'received_at' => null,
            ], $heuristic);
        }

        return null;
    }

    /**
     * Provider commission-payout notices are recorded as commission income:
     * amount = net commission received, no cash-balance effect, float +amount.
     *
     * @return array{reference: string, type: string, amount: float, customer_name: string, customer_phone: string, balance: float, commission: float, fee: float, received_at: string|null}|null
     */
    private function extractCommissionNotice(string $body): ?array
    {
        // AirtelMoney: "Dear Agent,You have received Airtelmoney commission:Amount Tsh 2,912.00, Tax is Tsh 0.00, Amount after Tax Tsh 2,912.00"
        if (stripos($body, 'you have received') !== false
            && stripos($body, 'airtelmoney commission') !== false
            && preg_match('/Amount\s+after\s+Tax\s+T[Ss][Hh]?\s*([\d,]+(?:\.\d+)?)/i', $body, $m) === 1) {
            return [
                'reference' => 'AIRTEL-COMM-'.strtoupper(substr(sha1($body), 0, 10)),
                'type' => 'commission_income',
                'amount' => (float) str_replace(',', '', $m[1]),
                'customer_name' => 'AirtelMoney Commission',
                'customer_phone' => '',
                'balance' => 0.0,
                'commission' => (float) str_replace(',', '', $m[1]),
                'fee' => 0.0,
                'received_at' => null,
            ];
        }

        // HaloPesa: "Normal commission for 09/2026 is ... you have received TZS 2,851.2 01/10/2026. Your new balance is TZS 1,186,851.2."
        if (stripos($body, 'normal commission for') !== false
            && preg_match('/you have received\s+TZS\s*([\d,]+(?:\.\d+)?)\s*(\d{1,2}\/\d{1,2}\/\d{2,4})?/i', $body, $m) === 1) {
            $date = $m[2] ?? null;

            return [
                'reference' => 'HALOPESA-COMM-'.($date !== null ? str_replace('/', '', $date) : strtoupper(substr(sha1($body), 0, 10))),
                'type' => 'commission_income',
                'amount' => (float) str_replace(',', '', $m[1]),
                'customer_name' => 'HaloPesa Commission',
                'customer_phone' => '',
                'balance' => preg_match('/new balance is\s+TZS\s*([\d,]+(?:\.\d+)?)/i', $body, $bm) === 1 ? (float) str_replace(',', '', $bm[1]) : 0.0,
                'commission' => (float) str_replace(',', '', $m[1]),
                'fee' => 0.0,
                'received_at' => $this->parseNoticeDate($date, null),
            ];
        }

        // MIXX: "Umepokea TSh 4,880 kutoka kwa Commission Pay, kumbukumbu ya malipo.: 26818784115110. REQ... 01/10/26 18:02."
        if (preg_match('/Umepokea\s+TSh\s+([\d,]+(?:\.\d+)?)\s+kutoka kwa\s+Commission Pay/i', $body, $m) === 1) {
            $ref = preg_match('/kumbukumbu ya malipo\.?:?\s*([0-9A-Za-z\-]+)/i', $body, $rm) === 1 ? $rm[1] : 'MIXX-COMM-'.strtoupper(substr(sha1($body), 0, 10));
            $date = preg_match('/(\d{1,2}\/\d{1,2}\/\d{2,4})\s+(\d{1,2}:\d{2}(?::\d{2})?)/', $body, $dm) === 1 ? $dm[1] : null;
            $time = preg_match('/(\d{1,2}\/\d{1,2}\/\d{2,4})\s+(\d{1,2}:\d{2}(?::\d{2})?)/', $body, $dm) === 1 ? $dm[2] : null;

            return [
                'reference' => (string) $ref,
                'type' => 'commission_income',
                'amount' => (float) str_replace(',', '', $m[1]),
                'customer_name' => 'Commission Pay',
                'customer_phone' => '',
                'balance' => preg_match('/Salio jipya\s+TSh\s+([\d,]+(?:\.\d+)?)/i', $body, $bm) === 1 ? (float) str_replace(',', '', $bm[1]) : 0.0,
                'commission' => (float) str_replace(',', '', $m[1]),
                'fee' => 0.0,
                'received_at' => $this->parseNoticeDate($date, $time),
            ];
        }

        return null;
    }

    private function parseNoticeDate(?string $date, ?string $time): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        $year = (int) preg_replace('/^\d{1,2}\/\d{1,2}\//', '', explode(' ', $date)[0]);
        $dateFormats = strlen((string) $year) === 2 ? ['d/m/y', 'd/m/Y'] : ['d/m/Y', 'd/m/y'];

        foreach ($dateFormats as $dateFormat) {
            try {
                $time = $time ?? '00:00';
                $format = preg_match_all('/:/', $time) > 1 ? $dateFormat.' H:i:s' : $dateFormat.' H:i';

                return Carbon::createFromFormat($format, $date.' '.$time)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @deprecated Provider allow-list is no longer a hard gate; unknown
     *             senders fall through to generic + heuristic layers.
     */
    private function isSupportedProvider(?string $provider, ?string $networkCode): bool
    {
        return true;
    }

    private function isRejected(string $body, ?string $sender): bool
    {
        $lowerBody = strtolower($body);
        $lowerSender = strtolower($sender ?? '');

        $hasCurrency = preg_match('/\bT(?:[Ss][Hh]|[Zz][Ss])\b/', $body)
            || preg_match('/\b\d{1,3}(?:,\d{3})+(?:\.\d+)?\b/', $body)
            || preg_match('/kiasi/i', $body);

        // OTP / security - only block if NO currency at all
        if (preg_match('/\b(otp|one time password|verification code|login code|usiposhare|do not share|code ya kuthibitisha|two.?factor|security code|digit code|expires in \d+ min|code sent to)\b/i', $body)) {
            if (! $hasCurrency) {
                return true;
            }
        }

        // Daily / account summary reports from providers - not transactions
        if (preg_match('/\b(you have done|do more transactions|cashin count|cashout count|cash in count|cash out count|daily (summary|report)|transaction summary)\b/i', $body)) {
            return true;
        }

        // Promo / marketing - only block if no currency + transaction verbs
        $promoMarkers = [
            'pata dakika',
            'mitandao yote',
            'bofya',
            'tinyurl',
            'piga *',
            'chagua',
            'uni ofa',
            'kwa siku 7',
            'siku 7',
            'pata ofa',
            'bonyeza',
            'zawadi',
            'shinda',
            'tumia',
            'of ya',
        ];

        $hasTxnVerb = preg_match('/\b(sent|received|withdrawn|deposited|transferred|paid|umetuma|umepokea|umetoa|umeweka|umelipa|imefanikiwa|imekamilika)\b/i', $body);
        $hasDirection = preg_match('/\b(from|to|kwa|kwenda|kutoka)\s+\w+/i', $body);

        foreach ($promoMarkers as $m) {
            if (str_contains($lowerBody, $m)) {
                // If it has currency AND a transaction verb or direction, not pure promo - allow
                if (! ($hasCurrency && ($hasTxnVerb || $hasDirection))) {
                    return true;
                }
            }
        }

        // SIM notifications
        if (preg_match('/\b(sim (card|notification|imebadilishwa)|simu ime)\b/i', $body)) {
            if (! $hasCurrency) {
                return true;
            }
        }

        // Network maintenance
        if (preg_match('/\b(maintenance|matengenezo|huduma itasitishwa|network (is|ime) (down|shut))\b/i', $body)) {
            if (! $hasCurrency) {
                return true;
            }
        }

        // Balance-only / service announcements: "balance is X" without any transaction context
        $balanceOnly = preg_match('/\b(balance is|salio lako ni|salio jipya ni|new balance is)\b/i', $body);
        if ($balanceOnly && ! $hasTxnVerb && ! $hasDirection && ! preg_match('/\b(tnx|txn|tid|reference)\b/i', $body)) {
            return true;
        }

        // If message has currency and any meaningful financial signals, let the parser try.
        // SmartHeuristicParser now handles unknown formats and generates fallback references.
        // Only hard-block obvious non-financial messages above.

        return false;
    }
}
