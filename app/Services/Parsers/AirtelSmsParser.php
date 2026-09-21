<?php

namespace App\Services\Parsers;

class AirtelSmsParser extends AbstractSmsParser
{
    protected function providerTemplates(): array
    {
        return [
            'airtel_float' => [
                'type' => 'bank_to_wallet',
                'pattern' => '/You Received\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*Tsh\s+from\s+(?P<phone>\d{6,15})\s*,?\s*(?P<customer>[^\.]+?)\s*\.?\s*New balance\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*Tsh\.?\s*Trans\.ID:\s*(?P<ref>[A-Z0-9\.\-]+)/is',
            ],
        ];
    }
}
