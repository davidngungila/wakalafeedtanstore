<?php

namespace App\Services\Parsers;

class HaloPesaSmsParser extends AbstractSmsParser
{
    protected function providerTemplates(): array
    {
        return [
            // IMEFANIKIWA! Tnx 6263421245180145. Umeweka 1,000 TZS kwa DAVID RASHID (0622239304) tarehe 21/09/2026 11:42:09. Ada: 0 TZS Salio jipya: 101,000.00 TZS. Preview Commission: 34 TZS
            'halo_deposit' => [
                'type' => 'deposit',
                'pattern' => '/Tnx\s*(?P<ref>[A-Z0-9]{8,20})\.?\s*Umeweka\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*TZS\s+kwa\s+(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\)\s+tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*:\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*TZS(?:.*?Preview\s+Commission\s*:\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*TZS)?/is',
            ],
            'halo_withdrawal' => [
                'type' => 'withdrawal',
                'pattern' => '/Tnx\s*(?P<ref>[A-Z0-9]{8,20})\.?\s*Umetoa\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*TZS\s+(?:kwa|kutoka\s+kwa)?\s*(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\)\s+tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*:\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*TZS(?:.*?Preview\s+Commission\s*:\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*TZS)?/is',
            ],
            'halo_send' => [
                'type' => 'send_money',
                'pattern' => '/Tnx\s*(?P<ref>[A-Z0-9]{8,20})\.?\s*Umetuma\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*TZS\s+kwa\s+(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\)\s+tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*:\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*TZS(?:.*?Preview\s+Commission\s*:\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*TZS)?/is',
            ],
            // Fallback generic HaloPesa that catches any Umeweka without full date/balance but still captures preview commission
            'halo_generic' => [
                'type' => 'deposit',
                'pattern' => '/Umeweka\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*TZS\s+kwa\s+(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\).*?Tnx\s*(?P<ref>[A-Z0-9]{8,20})/is',
            ],
        ];
    }
}
