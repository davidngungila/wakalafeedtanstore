<?php

namespace App\Services\Sms\Parsers;

class MixxParser
{
    public function templates(): array
    {
        return [
            'mixx_deposit' => [
                'type' => 'deposit',
                'pattern' => '/IMEFANIKIWA!?\s*Tnx\s*(?P<ref>[A-Z0-9]{8,20})\.?\s*Umeweka\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*TZS\s+kwa\s+(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\)\s+tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*:\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*TZS(?:.*?Preview\s+Commission\s*:\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*TZS)?/is',
            ],
            'mixx_withdrawal' => [
                'type' => 'withdrawal',
                'pattern' => '/IMEFANIKIWA!?\s*Tnx\s*(?P<ref>[A-Z0-9]{8,20})\.?\s*Umetoa\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*TZS\s+(?:kwa|kutoka\s+kwa)?\s*(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\)\s+tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*:\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*TZS/is',
            ],
            'mixx_float' => [
                'type' => 'bank_to_wallet',
                'pattern' => '/Umepokea\s+Pesa\s+kutoka\s+kwa:\s*Jina\s+la\s+Wakala:\s*(?P<customer>.+?)\s*,\s*Kiasi:\s*Tsh\s+(?P<amount>[\d,]+(?:\.\d+)?)\.\s*Salio\s+Jipya\s+ni\s+Tsh\s+(?P<balance>[\d,]+(?:\.\d+)?)\.\s*TxnID:\s*(?P<ref>[A-Z0-9]+)\.\s*(?P<date>\d{1,2}\/\d{1,2}\/\d{2})\s+(?P<time>\d{1,2}:\d{2})/is',
            ],
        ];
    }
}
