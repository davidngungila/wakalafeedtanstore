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
            // Float deposit from bank/union: You Received 1,000,000.00Tsh from 780566323,UNION FINANCIAL ... New balance 1,002,000.00 Tsh.Trans.ID: PP260921.1326.V28651
            'bank_float_deposit' => [
                'type' => 'bank_to_wallet',
                'pattern' => '/You Received\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*Tsh\s+from\s+(?P<phone>\d{6,15})\s*,?\s*(?P<customer>[^\.]+?)\s*\.?\s*New balance\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*Tsh\.?\s*Trans\.ID\s*:\s*(?P<ref>[A-Z0-9\.\-]+)/is',
            ],
        ];
    }
}
