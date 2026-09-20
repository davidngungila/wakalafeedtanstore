<?php

namespace App\Services\Parsers;

interface SmsParserContract
{
    /**
     * Parses a provider SMS body into the fields the system stores on a
     * transaction. A message that matches nothing yields empty values.
     *
     * @return array{reference: string, type: string, amount: float, customer_name: string, customer_phone: string, balance: float, received_at: string|null}
     */
    public function parse(string $body): array;
}
