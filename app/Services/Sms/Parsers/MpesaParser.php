<?php

namespace App\Services\Sms\Parsers;

/**
 * Vodacom M-Pesa — strict approved templates.
 * Network keywords: MPESA, VODACOM
 * Example: "TID12345 Confirmed. You have received TZS 10,000 from JOHN DOE 0712345678 on 21/09/2026 at 11:42. New balance is TZS 101,000."
 */
class MpesaParser
{
    public function templates(): array
    {
        return [
            'mpesa_deposit' => [
                'type' => 'deposit',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:]+.*?(?:IMEFANIKIWA|Confirmed|confirmed).*?(?:received|deposited|credited).*?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).*?from\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b(?:.*?on\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?))?.*?(?:(?:balance is|New balance is|Saldo|Salio)\s*(?:is|:)?\s*TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
            'mpesa_deposit_simple' => [
                'type' => 'deposit',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:.\-]+.*?You have received\s+TZS\s+(?P<amount>[\d,]+(?:\.\d+)?)\s+from\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b/is',
            ],
            'mpesa_deposit_sw' => [
                'type' => 'deposit',
                'pattern' => '/IMEFANIKIWA!?\s*Tnx\s*(?P<ref>[A-Z0-9]{8,20})\.?\s*Umeweka\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*TZS\s+kwa\s+(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\)\s+tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*:\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*TZS(?:.*?Preview\s+Commission\s*:\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*TZS)?/is',
            ],
        ];
    }
}
