<?php

namespace App\Services\Sms\Parsers;

class BankParser
{
    public function templates(): array
    {
        return [
            'bank_deposit' => [
                'type' => 'deposit',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:]+.*?You have received\s+TZS\s+(?P<amount>[\d,]+(?:\.\d+)?)\s+from\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b/is',
            ],
            'bank_deposit_confirmed' => [
                'type' => 'deposit',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:]+.*?confirmed.*?You have received\s+TZS\s+(?P<amount>[\d,]+(?:\.\d+)?)\s+from\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b/is',
            ],
        ];
    }
}
