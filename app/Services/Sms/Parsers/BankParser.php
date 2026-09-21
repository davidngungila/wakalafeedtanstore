<?php

namespace App\Services\Sms\Parsers;

class BankParser
{
    public function templates(): array
    {
        return [
            'bank_received' => [
                'type' => 'withdrawal',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:]+.*?You have received\s+TZS\s+(?P<amount>[\d,]+(?:\.\d+)?)\s+from\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b/is',
            ],
            'bank_received_confirmed' => [
                'type' => 'withdrawal',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:]+.*?confirmed.*?You have received\s+TZS\s+(?P<amount>[\d,]+(?:\.\d+)?)\s+from\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b/is',
            ],
        ];
    }
}
