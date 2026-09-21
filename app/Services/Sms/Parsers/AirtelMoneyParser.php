<?php

namespace App\Services\Sms\Parsers;

class AirtelMoneyParser
{
    public function templates(): array
    {
        return [
            'airtel_received' => [
                'type' => 'withdrawal',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:]+.*?received.*?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).*?from\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b.*?on\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?(?:balance\s+is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
            'airtel_withdrawal' => [
                'type' => 'withdrawal',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:]+.*?withdrawn.*?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).*?from\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b.*?on\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?(?:balance\s+is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
            'airtel_float' => [
                'type' => 'bank_to_wallet',
                'pattern' => '/You Received\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*Tsh\s+from\s+(?P<phone>\d{6,15})\s*,?\s*(?P<customer>[^\.]+?)\s*\.?\s*New balance\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*Tsh\.?\s*Trans\.ID:\s*(?P<ref>[A-Z0-9\.\-]+)/is',
            ],
        ];
    }
}
