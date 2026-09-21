<?php

namespace App\Services\Sms;

/**
 * Strict SMS → Transaction extractor.
 *
 * Usage:
 *   $result = $extractor->extract($body, $provider, $sender, $networkCode);
 *   if ($result === null) => not a transaction (promo/OTP/balance-only/etc. -> NEEDS_REVIEW)
 *   else => valid transaction fields
 *
 * Implements the spec:
 *   SUPPORTED NETWORK + SUCCESS INDICATOR + VALID REFERENCE + VALID AMOUNT + ACTION + DATE/TIME
 *   All must be present via an approved template for that network/provider.
 */
class SmsTransactionExtractor
{
    private TransactionTemplateEngine $engine;

    public function __construct(?TransactionTemplateEngine $engine = null)
    {
        $this->engine = $engine ?? new TransactionTemplateEngine;
    }

    /**
     * @return array{reference: string, type: string, amount: float, customer_name: string, customer_phone: string, balance: float, commission: float, fee: float, received_at: string|null}|null
     */
    public function extract(string $body, ?string $provider, ?string $sender = null, ?string $networkCode = null): ?array
    {
        // Critical protection: reject promo/marketing/OTP etc. before even trying templates
        if ($this->isRejected($body, $sender)) {
            return null;
        }

        // Provider must be a supported transaction network; generic/unknown never creates a transaction (strict)
        if (! $this->isSupportedProvider($provider, $networkCode)) {
            return null;
        }

        $matched = $this->engine->match($body, $provider);

        if ($matched === null) {
            return null;
        }

        // Strict validation already done in engine, but double-check reference/amount
        if ($matched['reference'] === '' || $matched['amount'] <= 0) {
            return null;
        }

        return $matched;
    }

    private function isSupportedProvider(?string $provider, ?string $networkCode): bool
    {
        $supportedProviders = ['mpesa', 'airtel', 'mixx', 'halopesa', 'tigo', 'ttcl', 'azam', 'bank'];
        if ($provider !== null && in_array($provider, $supportedProviders, true)) {
            return true;
        }

        return false;
    }

    private function isRejected(string $body, ?string $sender): bool
    {
        $lowerBody = strtolower($body);
        $lowerSender = strtolower($sender ?? '');

        // OTP / security
        if (preg_match('/\b(otp|one time password|verification code|login code|usiposhare|do not share|code ya kuthibitisha)\b/i', $body)) {
            // But allow if it also contains financial success markers - OTP rarely contains IMEFANIKIWA + Tnx + Salio
            if (! str_contains($lowerBody, 'imefanikiwa') && ! str_contains($lowerBody, 'salio jipya')) {
                return true;
            }
        }

        // Promo / marketing
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

        foreach ($promoMarkers as $m) {
            if (str_contains($lowerBody, $m)) {
                // If it also has financial success markers, it's not pure promo - allow
                if (! str_contains($lowerBody, 'imefanikiwa') && ! str_contains($lowerBody, 'salio jipya')) {
                    return true;
                }
            }
        }

        // Balance-only / service announcements / maintenance
        if (preg_match('/\b(balance is|salio lako ni)\b/i', $body) && ! preg_match('/\b(imefanikiwa|umepokea|umeweka|umetoa|received|txn|tnx|transaction id)\b/i', $body)) {
            return true;
        }

        // SIM notifications
        if (preg_match('/\b(sim (card|notification|imebadilishwa)|simu ime)\b/i', $body)) {
            return true;
        }

        // Network maintenance
        if (preg_match('/\b(maintenance|matengenezo|huduma itasitishwa|network (is|ime) (down|shut))\b/i', $body)) {
            return true;
        }

        // "You have received..." without valid reference - strict templates require reference
        // If body contains "you have received" but no reference keyword or leading ref, it's not valid per spec
        // But allow leading reference like "TIGO0001 confirmed. You have received..." where ref is at start
        $hasLeadingRef = preg_match('/^\s*[A-Z0-9]{5,12}[\s:]+/i', $body);
        if (preg_match('/you have received/i', $body) && ! preg_match('/\b(tnx|txn|reference|transaction id)\b/i', $body) && ! $hasLeadingRef) {
            return true;
        }

        // Strict: must have success indicator and reference (either keyword or leading ref)
        $hasSuccess = preg_match('/\b(imefanikiwa|confirmed|successful|successfully|completed|imekamilika|umepokea|umeweka|umetoa|umetuma)\b/i', $body);
        $hasRefKeyword = preg_match('/\b(tnx|txn|reference|transaction id)\b/i', $body);

        if (! $hasSuccess && ! $hasRefKeyword && ! $hasLeadingRef) {
            // No success and no reference at all => not a valid transaction
            if (preg_match('/\bTZS\b/i', $body)) {
                return true;
            }
        }

        return false;
    }
}
