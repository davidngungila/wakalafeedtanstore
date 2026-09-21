<?php

namespace App\Services\Sms\Parsers;

class TtclPesaParser
{
    public function templates(): array
    {
        // TTCL Pesa placeholders - strict, will only match approved TTCL success messages.
        // Add real TTCL templates once formats are available; unknown formats will be stored as non-transaction (NEEDS_REVIEW).
        return [
            'ttcl_deposit' => [
                'type' => 'deposit',
                'pattern' => '/^(?P<ref>TTCL[A-Z0-9]{6,12})[\s:]+.*?IMEFANIKIWA.*?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).*?kwa\s+(?P<customer>.+?)\s+\((?P<phone>0\d{9,10})\).*?tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*:\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*TZS/is',
            ],
        ];
    }
}
