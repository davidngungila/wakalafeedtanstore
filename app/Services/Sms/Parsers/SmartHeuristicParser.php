<?php

namespace App\Services\Sms\Parsers;

/**
 * Network-agnostic heuristic parser.
 *
 * Uses universal pattern matching to extract transaction fields from ANY
 * mobile-money SMS format (Airtel, M-Pesa, Tigo, HaloPesa, Azam, TTCL, Mixx,
 * or a completely new network never seen before).
 *
 * Strategy: instead of per-network templates, look for universal signals:
 *   - Currency amounts: numbers with commas near Tsh/TZS currency markers
 *   - Action verbs: Sent, Received, Withdrawn, Deposited, Paid, Transferred,
 *                   Umetuma, Umepokea, Umeweka, Umetoa, Umelipa, IMEFANIKIWA
 *   - Direction: "to NAME PHONE" = send_money/out; "from NAME PHONE" = receive/in
 *   - Phone numbers: 9-10 digit sequences (with or without leading 0)
 *   - References: explicit TID:/TXN:/TNX:/Trans.ID: labels, or leading alphanumeric codes
 *   - Commission: "commission before Tax is", "Preview Commission:", "Ada", "Malipo"
 *   - Balance: "Balance N Tsh", "Salio jipya:", "New balance"
 *   - Names: word sequences appearing next to "to"/"from"/"kwa" and before a phone or period
 *
 * Enhanced leniency: falls back to generated references when no explicit ref
 * exists, and accepts a wider set of amount/type signals so brand-new SMS
 * formats from unknown networks still parse successfully.
 */
class SmartHeuristicParser
{
    /**
     * Try to heuristically extract a transaction from ANY SMS format.
     *
     * @return array{reference: string, type: string, amount: float, customer_name: string, customer_phone: string, balance: float, commission: float, fee: float}|null
     */
    public function extract(string $body): ?array
    {
        $cleanBody = preg_replace('/\s+/', ' ', trim($body));

        if ($cleanBody === '') {
            return null;
        }

        $amount = $this->extractAmount($cleanBody);
        if ($amount <= 0) {
            return null;
        }

        $type = $this->detectType($cleanBody);
        if ($type === '') {
            $type = $this->inferTypeFromStructure($cleanBody);
            if ($type === '') {
                return null;
            }
        }

        $reference = $this->extractReference($cleanBody);
        if ($reference === '') {
            $reference = $this->generateFallbackReference($cleanBody);
        }

        if ($reference === '') {
            return null;
        }

        $phone = $this->extractPhone($cleanBody);
        $name = $this->extractName($cleanBody, $phone, $type);
        $balance = $this->extractBalance($cleanBody);
        $commission = $this->extractCommission($cleanBody);
        $fee = $this->extractFee($cleanBody);

        return [
            'reference' => $reference,
            'type' => $type,
            'amount' => $amount,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'balance' => $balance,
            'commission' => $commission,
            'fee' => $fee,
        ];
    }

    /**
     * Extract the transaction amount. Highest priority to values anchored by
     * action verbs (Sent / Received / Umepokea etc.) because those are the
     * principal transaction value, never commission or balance.
     *
     * Explicitly avoids matching numbers that appear after balance/commission
     * keywords when a verb-anchored value is available.
     */
    private function extractAmount(string $body): float
    {
        $cur = 'T(?:[Ss][Hh]|[Zz][Ss])';
        $curAny = '(?:T(?:[Ss][Hh]|[Zz][Ss])|Shs?|Kshs?|Tanzania\s*Shillings?)';

        $actionVerbs = [
            'sent', 'transferr', 'umetuma', 'tuma', 'kutuma',
            'paid', 'umelipa', 'lipa', 'malipo', 'imekulipwa',
            'withdrawn', 'withdraw', 'cash\s*out', 'cashout', 'umetoa', 'toa',
            'received', 'receive', 'umepokea', 'pokea', 'kupokea',
            'deposited', 'deposit', 'umeweka', 'weka', 'kuweka',
            'credited', 'credit', 'imefanikiwa', 'imekamilika', 'success',
            'uliza', 'kiasi', 'amount',
            'bill\s*pyt', 'payment',
            'imeongezwa', 'imepunguzwa',
        ];

        $actionJoined = implode('|', $actionVerbs);

        $actionPatterns = [
            "/(?:$actionJoined)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*$curAny/iu",
            "/(?:$actionJoined)\s+$curAny\s*(?P<amount>[\d,]+(?:\.\d+)?)/iu",
            "/(?:$actionJoined)\s*[:=]\s*(?P<amount>[\d,]+(?:\.\d+)?)\s*$curAny/iu",
            "/(?:$actionJoined)\s*[:=]\s*$curAny\s*(?P<amount>[\d,]+(?:\.\d+)?)/iu",
        ];

        foreach ($actionPatterns as $pattern) {
            if (preg_match($pattern, $body, $m)) {
                $val = (float) str_replace(',', '', $m['amount']);
                if ($val > 0) {
                    return $val;
                }
            }
        }

        $noBalanceCommission = preg_replace(
            "/(?:balance|salio|commission|malipo|ada|fee|charge|vAT|tax|uma|kwa|huduma|deduction)(?:\s+jipya)?\s*[:=]?\s*[TZShzs\s]*[\d,]+(?:\.\d+)?\s*(?:$curAny)?/iu",
            '',
            $body
        );

        $fallbackPatterns = [
            "/$curAny\s+(?P<amount>[\d,]+(?:\.\d+)?)/iu",
            "/(?P<amount>[\d,]+(?:\.\d+)?)\s*$curAny/iu",
            '/(?P<amount>[\d,]+(?:\.\d+)?)\s*\/\s*=/',
            "/(?:amount|kiasi)\s*[:=]\s*$curAny\s*(?P<amount>[\d,]+(?:\.\d+)?)/iu",
            "/(?:amount|kiasi)\s*[:=]\s*(?P<amount>[\d,]+(?:\.\d+)?)/iu",
            "/=\s*(?P<amount>[\d,]+(?:\.\d+)?)\b/",
            "/(?<=^|[\s])(?P<amount>\d{1,3}(?:,\d{3})+(?:\.\d+)?)(?=$|[\s.])/",
        ];

        foreach ($fallbackPatterns as $pattern) {
            if (preg_match($pattern, $noBalanceCommission, $m)) {
                $val = (float) str_replace(',', '', $m['amount']);
                if ($val > 0) {
                    return $val;
                }
            }
        }

        foreach ($fallbackPatterns as $pattern) {
            if (preg_match($pattern, $body, $m)) {
                $val = (float) str_replace(',', '', $m['amount']);
                if ($val > 0) {
                    return $val;
                }
            }
        }

        return 0.0;
    }

    /**
     * Detect transaction type/direction from action verbs and prepositions.
     */
    private function detectType(string $body): string
    {
        $lower = strtolower($body);

        $sendVerbs = [
            'sent ', 'transferr', 'umetuma', 'tuma ', 'kutuma',
            'cash out', 'cashout', 'withdrawn', 'withdraw', 'umetoa', 'toa ',
            'paid ', 'umelipa', 'lipa ', 'malipo', 'imekulipwa',
            'imepunguzwa', 'deducted', 'debited',
        ];
        $receiveVerbs = [
            'received', 'receive', 'umepokea', 'pokea', 'kupokea',
            'deposited', 'deposit', 'umeweka', 'weka ', 'kuweka',
            'credited', 'credit', 'imefanikiwa', 'imekamilika', 'success',
            'imeongezwa', 'added', 'incoming',
        ];

        $hasSend = false;
        foreach ($sendVerbs as $v) {
            if (str_contains($lower, $v)) {
                $hasSend = true;
                break;
            }
        }

        $hasReceive = false;
        foreach ($receiveVerbs as $v) {
            if (str_contains($lower, $v)) {
                $hasReceive = true;
                break;
            }
        }

        $isBillKeyword = str_contains($lower, 'bill') || str_contains($lower, 'lipa ') || str_contains($lower, 'umelipa') || str_contains($lower, 'payment');
        $isMalipoAsAction = str_contains($lower, 'malipo') && ! preg_match('/(?:salio|balance|commission|ada|huduma|tax|vat).*?malipo|malipo\s*ya\s+huduma|malipo\s*[:=]|malipo\s*[\d,.]/is', $lower);
        if ($isBillKeyword || $isMalipoAsAction) {
            if (preg_match('/\b(airtime|dakika|data|mb|gb|bundle|internet)\b/i', $body)) {
                return 'airtime';
            }

            return 'bill_payment';
        }

        if (str_contains($lower, 'airtime') || str_contains($lower, 'dakika') || str_contains($lower, 'bundle') || preg_match('/\b(data|mb|gb|internet)\b/i', $body)) {
            if ($hasSend || ! $hasReceive) {
                return 'airtime';
            }
        }

        if (str_contains($lower, 'float') || (str_contains($lower, 'bank') && ($hasReceive || str_contains($lower, 'to wallet') || str_contains($lower, 'kwa mfuko') || str_contains($lower, 'to mfuko')))) {
            return $hasReceive ? 'bank_to_wallet' : 'wallet_to_bank';
        }

        if (str_contains($lower, 'union financial') || str_contains($lower, 'kiasi:tsh') || str_contains($lower, 'kiasi: tsh')) {
            return $hasReceive ? 'bank_to_wallet' : 'deposit';
        }

        if ($hasReceive || (str_contains($lower, ' from ') && ! $hasSend)) {
            return 'deposit';
        }

        if ($hasSend || str_contains($lower, ' to ') || str_contains($lower, ' kwa ')) {
            if (preg_match('/\b(cash\s*out|withdraw|to\s+agent|to\s+dola|agent|dola)\b/i', $body)) {
                return 'withdrawal';
            }

            return 'send_money';
        }

        return '';
    }

    /**
     * Infer type from structural hints when no action verb matched.
     * Uses direction prepositions, agent/merchant keywords etc.
     */
    private function inferTypeFromStructure(string $body): string
    {
        $lower = strtolower($body);

        if (preg_match('/\bfrom\s+\w+/i', $body) && ! preg_match('/\bto\s+\w+/i', $body)) {
            return 'deposit';
        }

        if (preg_match('/\bto\s+\w+/i', $body) && ! preg_match('/\bfrom\s+\w+/i', $body)) {
            if (preg_match('/\b(agent|dola|wakala)\b/i', $body)) {
                return 'withdrawal';
            }

            return 'send_money';
        }

        if (preg_match('/\b(kwa\s+\w+|kwenda\s+\w+)\b/i', $body)) {
            if (preg_match('/\b(wakala|dola|agent)\b/i', $body)) {
                return 'withdrawal';
            }

            return 'send_money';
        }

        if (str_contains($lower, 'wakala') || str_contains($lower, 'agent')) {
            if (str_contains($lower, 'kwa') || str_contains($lower, 'to')) {
                return 'withdrawal';
            }

            return 'deposit';
        }

        if (preg_match('/\b\d{9,10}\b/', $body)) {
            return 'deposit';
        }

        return '';
    }

    /**
     * Extract the provider reference / transaction ID.
     * Priority: explicit labels (TID: TXN: TNX: Trans.ID:) > leading alphanumeric code
     */
    private function extractReference(string $body): string
    {
        $patterns = [
            '/\b(?:TID|TXN|TNX|Ref|Reference|Trans(?:action)?\.?\s*ID)\s*[:#\-]\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]{3,})/i',
            '/\b(?:ID|Kodi|Namba|No\.?|Code)\s*[:#\-]\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]{3,})/i',
            '/\b(?:Transaction|Muamala)\s*[:#\-]\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]{3,})/i',
            '/\b(?P<ref>[A-Z]{2,}\d{2,}[A-Z0-9.\-]*)\b/',
            '/\b(?P<ref>\d{8,20})\b/',
            '/\b(?P<ref>[A-Z0-9]{5,}[-][A-Z0-9]{2,})\b/i',
            '/#(?P<ref>[A-Z0-9]{5,})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $m)) {
                $ref = trim(strtoupper($m['ref']), " .:\t\n\r\0\x0B");
                $clean = preg_replace('/[.\-#]/', '', $ref);
                if (strlen($ref) >= 4 && $clean !== '' && ctype_alnum($clean)) {
                    return $ref;
                }
            }
        }

        if (preg_match('/^\s*(?P<ref>[A-Z0-9]{4,16})[\s:]/', $body, $m)) {
            return strtoupper(trim($m['ref']));
        }

        if (preg_match('/^\s*(?P<ref>[A-Za-z0-9]{4,16})[\s\.]/', $body, $m)) {
            $ref = strtoupper(trim($m['ref']));
            if (preg_match('/[A-Z]/', $ref) && preg_match('/[0-9]/', $ref)) {
                return $ref;
            }
        }

        return '';
    }

    /**
     * Generate a deterministic fallback reference when no explicit reference
     * ID is present in the SMS body. Uses the message hash + amount/type so
     * duplicate SMS still deduplicate correctly.
     */
    private function generateFallbackReference(string $body): string
    {
        $hash = hash('sha256', $body);

        return 'AUTO-'.strtoupper(substr($hash, 0, 12));
    }

    /**
     * Extract customer phone number. Matches Tanzanian formats:
     *   0XXXXXXXXX (10-digit with leading 0)
     *   XXXXXXXXX  (9-digit without leading 0)
     *   +255XXXXXXXXX (international format)
     */
    private function extractPhone(string $body): string
    {
        $patterns = [
            '/\b(?P<phone>0\d{9})\b/',
            '/\b(?P<phone>[67]\d{8})\b/',
            '/\+255(?P<phone>\d{9})\b/',
            '/\b255(?P<phone>[67]\d{8})\b/',
            '/\b(?P<phone>0\d{3}[-\s]?\d{3}[-\s]?\d{3})\b/',
            '/\((?P<phone>0?\d{9,10})\)/',
            '/["\'](?P<phone>0\d{9})["\']/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $m)) {
                $phone = preg_replace('/[-\s]/', '', $m['phone']);
                if (strlen($phone) === 9) {
                    $phone = '0'.$phone;
                }
                if (strlen($phone) === 10 && str_starts_with($phone, '0')) {
                    return $phone;
                }
            }
        }

        if (preg_match_all('/\b(\d{9,10})\b/', $body, $all)) {
            foreach ($all[1] as $candidate) {
                if (strlen($candidate) === 9 && preg_match('/^[67]/', $candidate)) {
                    return '0'.$candidate;
                }
                if (strlen($candidate) === 10 && str_starts_with($candidate, '0')) {
                    return $candidate;
                }
            }
        }

        return '';
    }

    /**
     * Extract customer name. Strategy:
     *   For send types: "to [NAME] [PHONE or .]"
     *   For receive types: "from [NAME] [PHONE or .]"
     *   Otherwise look for all-uppercase word pairs (e.g. KELVIN MUSHI)
     */
    private function extractName(string $body, string $phone, string $type): string
    {
        $phonePattern = $phone !== '' ? preg_quote($phone, '/') : '(?:0\d{9}|[67]\d{8})';
        $isReceive = in_array($type, ['withdrawal', 'deposit', 'bank_to_wallet'], true);

        if ($isReceive) {
            $patterns = [
                '/from\s+(?P<name>[A-Za-z][A-Za-z\'\s.]{1,59}?)\s*[\s.]\s*(?:'.$phonePattern.'|\(|Tsh|TZS|kwa|mwenye|on|$)/iu',
                '/kwa\s+(?P<name>[A-Za-z][A-Za-z\'\s.]{1,59}?)\s*[\s.]\s*(?:'.$phonePattern.'|\(|Tsh|TZS|kwa|$)/iu',
                '/kutoka\s+(?P<name>[A-Za-z][A-Za-z\'\s.]{1,59}?)\s*[\s.]\s*(?:'.$phonePattern.'|\(|Tsh|TZS|kwa|$)/iu',
            ];
        } else {
            $patterns = [
                '/to\s+(?P<name>[A-Za-z][A-Za-z\'\s.]{1,59}?)\s*[\s.]\s*(?:'.$phonePattern.'|\(|Tsh|TZS|kwa|on|$)/iu',
                '/kwenda\s+(?P<name>[A-Za-z][A-Za-z\'\s.]{1,59}?)\s*[\s.]\s*(?:'.$phonePattern.'|\(|Tsh|TZS|$)/iu',
                '/kwa\s+(?P<name>[A-Za-z][A-Za-z\'\s.]{1,59}?)\s*[\s.]\s*(?:'.$phonePattern.'|\(|Tsh|TZS|$)/iu',
            ];
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $m)) {
                $name = trim(preg_replace('/\s+/', ' ', $m['name']), " .\t\n\r\0\x0B");
                if ($name !== '' && strlen($name) >= 2 && ! is_numeric(str_replace(' ', '', $name))) {
                    return $this->cleanName($name);
                }
            }
        }

        if (preg_match('/(?P<name>[A-Z]{2,}(?:\s+[A-Z]{1,}\.?){1,4})/', $body, $m)) {
            $name = trim($m['name']);
            if (! is_numeric(str_replace(' ', '', $name)) && strlen($name) <= 80) {
                return $this->cleanName($name);
            }
        }

        if (preg_match('/(?P<name>[A-Z][a-z]{1,}(?:\s+[A-Z][a-z]{1,}){1,4})/', $body, $m)) {
            $name = trim($m['name']);
            $stopwords = ['Balance', 'Commission', 'Reference', 'Transaction', 'New', 'TID', 'TXN', 'TNX', 'Amount', 'Total', 'Fee', 'Tax', 'Salio', 'Ada', 'Malipo', 'Kiasi', 'Maelezo'];
            foreach ($stopwords as $s) {
                if (stripos($name, $s) === 0) {
                    return '';
                }
            }

            return $this->cleanName($name);
        }

        if (preg_match('/\b(?P<name>Mr\.?\s+[A-Z][a-z]+|Mrs\.?\s+[A-Z][a-z]+|Ms\.?\s+[A-Z][a-z]+|Dr\.?\s+[A-Z][a-z]+)\b/', $body, $m)) {
            return $this->cleanName($m['name']);
        }

        return '';
    }

    /**
     * Trim and normalize a customer name extracted by any pattern.
     */
    private function cleanName(string $name): string
    {
        $name = preg_replace('/\s+/', ' ', trim($name, " .\t\n\r\0\x0B"));
        $name = preg_replace('/^[\.\s]+|[\.\s]+$/', '', $name);

        if (strlen($name) < 2 || is_numeric(str_replace(' ', '', $name))) {
            return '';
        }

        return $name;
    }

    /**
     * Extract the resulting balance after the transaction.
     */
    private function extractBalance(string $body): float
    {
        $cur = 'T(?:[Ss][Hh]|[Zz][Ss])';

        $patterns = [
            "/\b(?:Balance|Salio(?:\s+jipya)?|New\s+balance|Mizania|Salio)\s*(?:\s*(?:is|ni|=|:)|ni\s+)?\s*$cur\s+(?P<balance>[\d,]+(?:\.\d+)?)/iu",
            "/\b(?:Balance|Salio(?:\s+jipya)?|New\s+balance|Mizania|Salio)\s*(?:\s*(?:is|ni|=|:)|ni\s+)?\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*$cur/iu",
            "/\b(?:Balance|Salio(?:\s+jipya)?|New\s+balance|Mizania|Salio)\s*(?:\s*(?:is|ni|=|:)|ni\s+)?\s*=\s*(?P<balance>[\d,]+(?:\.\d+)?)/iu",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $m)) {
                return (float) str_replace(',', '', $m['balance']);
            }
        }

        return 0.0;
    }

    /**
     * Extract commission / charge. Multiple phrasings:
     *   "commission before Tax is N Tsh"
     *   "Preview Commission: N TZS"
     *   "Ada: N", "Malipo ya huduma: N", "Fee: N"
     */
    private function extractCommission(string $body): float
    {
        $cur = 'T(?:[Ss][Hh]|[Zz][Ss])';

        $patterns = [
            "/commission\s+(?:before\s+Tax\s+|before\s+tax\s+)?is\s+(?P<val>[\d,]+(?:\.\d+)?)\s*$cur/i",
            "/(?:Preview\s+)?Commission\s*[:=]\s*(?P<val>[\d,]+(?:\.\d+)?)\s*$cur/i",
            "/\b(?:commission|ada(?:\s+ya\s+huduma)?|malipo(?:\s+ya\s+huduma)?|fee|charge|vAT|tax)\s*[:=]\s*$cur\s*(?P<val>[\d,]+(?:\.\d+)?)/i",
            "/\b(?:commission|ada(?:\s+ya\s+huduma)?|malipo(?:\s+ya\s+huduma)?|fee|charge|vAT|tax)\s*[:=]\s*(?P<val>[\d,]+(?:\.\d+)?)\s*$cur/i",
            "/malipo\s+(?P<val>[\d,]+(?:\.\d+)?)\s*$cur/i",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $m)) {
                return (float) str_replace(',', '', $m['val']);
            }
        }

        return 0.0;
    }

    /**
     * Extract a separate transaction fee where present.
     */
    private function extractFee(string $body): float
    {
        $cur = 'T(?:[Ss][Hh]|[Zz][Ss])';

        $patterns = [
            "/\b(?:fee|ada(?:\s+ya)?|processing|transaction\s+fee|kodi)\s*[:=]\s*(?P<val>[\d,]+(?:\.\d+)?)\s*$cur/i",
            "/\b(?:fee|ada(?:\s+ya)?|processing|transaction\s+fee|kodi)\s*[:=]\s*$cur\s*(?P<val>[\d,]+(?:\.\d+)?)/i",
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $body, $m)) {
                return (float) str_replace(',', '', $m['val']);
            }
        }

        return 0.0;
    }
}
