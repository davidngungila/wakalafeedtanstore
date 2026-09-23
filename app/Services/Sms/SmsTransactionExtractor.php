<?php

namespace App\Services\Sms;

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
        if (preg_match('/\b(otp|one time password|verification code|login code|usiposhare|do not share|code ya kuthibitisha)\b/i', $body)) {
            if (! $hasCurrency) {
                return true;
            }
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
