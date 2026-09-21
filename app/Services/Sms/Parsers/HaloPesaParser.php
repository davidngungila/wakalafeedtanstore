<?php

namespace App\Services\Sms\Parsers;

class HaloPesaParser
{
    public function templates(): array
    {
        return [
            'halo_deposit' => [
                'type' => 'deposit',
                'pattern' => '/IMEFANIKIWA!?\s*Tnx\s*(?P<ref>[A-Z0-9]{8,20})\.?\s*Umeweka\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*TZS\s+kwa\s+(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\)\s+tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?(?:Ada\s*:\s*(?P<fee>[\d,]+(?:\.\d+)?)\s*TZS)?.*?Salio\s+jipya\s*:\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*TZS(?:.*?Preview\s+Commission\s*:\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*TZS)?/is',
            ],
            'halo_withdrawal' => [
                'type' => 'withdrawal',
                'pattern' => '/IMEFANIKIWA!?\s*Tnx\s*(?P<ref>[A-Z0-9]{8,20})\.?\s*Umetoa\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*TZS\s+(?:kwa|kutoka\s+kwa)?\s*(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\)\s+tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*:\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*TZS(?:.*?Preview\s+Commission\s*:\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*TZS)?/is',
            ],
            'halo_received' => [
                'type' => 'withdrawal',
                'pattern' => '/IMEFANIKIWA!?\s*Tnx\s*(?P<ref>[A-Z0-9]{8,20})\.?\s*Umepokea\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*TZS\s+kutoka\s+kwa\s+(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\)\s+tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*:\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*TZS(?:.*?Preview\s+Commission\s*:\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*TZS)?/is',
            ],
            'halo_float_deposit' => [
                'type' => 'bank_to_wallet',
                'pattern' => '/UMEWEKEWA\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*(?:TZS|Tsh)?\s*(?:KUTOKA\s+(?P<customer>.+?))?\s+TAREHE\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s*(?P<time>\d{1,2}:\d{2}(?::\d{2})?)?\s*SALIO\s+JIPY(?:A)?\s*(?:NI)?\s*Tsh\s+(?P<balance>[\d,]+(?:\.\d+)?)(?:\s*TxnID:\s*(?P<ref>[A-Z0-9]+))?/is',
            ],
            'halo_send' => [
                'type' => 'send_money',
                'pattern' => '/IMEFANIKIWA!?\s*Tnx\s*(?P<ref>[A-Z0-9]{8,20})\.?\s*Umetuma\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*TZS\s+kwa\s+(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\)\s+tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*:\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*TZS/is',
            ],
        ];
    }
}
