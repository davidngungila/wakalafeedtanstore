<?php

namespace App\Services\Parsers;

/**
 * Bank SMS differ from wallet operators: the reference is often a statement
 * line and the customer is not always a person, so the generic financial
 * templates still apply as a baseline and bank-specific phrasing can be added
 * here without touching the wallet parsers.
 */
class BankSmsParser extends AbstractSmsParser
{
    protected function providerTemplates(): array
    {
        return [
            'bank_float' => [
                'type' => 'bank_to_wallet',
                'pattern' => '/You Received\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*Tsh\s+from\s+(?P<phone>\d{6,15})\s*,?\s*(?P<customer>[^\.]+?)\s*\.?\s*New balance\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*Tsh\.?\s*Trans\.ID:\s*(?P<ref>[A-Z0-9\.\-]+)/is',
            ],
        ];
    }
}
