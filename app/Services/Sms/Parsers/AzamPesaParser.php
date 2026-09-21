<?php

namespace App\Services\Sms\Parsers;

class AzamPesaParser
{
    public function templates(): array
    {
        // Azam Pesa placeholders - strict, only approved Azam success messages.
        return [
            'azam_deposit' => [
                'type' => 'deposit',
                'pattern' => '/^(?P<ref>AZAM[A-Z0-9]{6,12})[\s:]+.*?IMEFANIKIWA.*?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).*?kwa\s+(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\).*?tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*:\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*TZS/is',
            ],
        ];
    }
}
