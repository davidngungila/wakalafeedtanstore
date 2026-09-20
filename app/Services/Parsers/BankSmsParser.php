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
        return [];
    }
}
