<?php

namespace App\Services\Parsers;

class TigoSmsParser extends AbstractSmsParser
{
    /**
     * Tigo Pesa messages use Swahili phrasing and carry the confirmation code
     * in a "Transaction ID:" segment instead of a leading reference:
     *
     *   Tigo Pesa: Umepokea TZS 50,000 kutoka kwa JOHN DOE 0712345678.
     *   Transaction ID: MP250920ABC123. Salio lako ni TZS 350,000.
     */
    protected function providerTemplates(): array
    {
        return [
            'tigo_received' => [
                'type' => 'withdrawal',
                'pattern' => '/Tigo[^\n]*?:?\s*Umepokea\s+TZS\s+(?P<amount>[\d,]+(?:\.\d+)?)\s+kutoka\s+kwa\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b.*?Transaction\s+ID\s*[:\-]\s*(?P<ref>[A-Z0-9]{5,20})\.?\s*(?:Salio\s+lako\s+ni\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
            'tigo_float' => [
                'type' => 'bank_to_wallet',
                'pattern' => '/Umepokea\s+Pesa\s+kutoka\s+kwa:\s*Jina\s+la\s+Wakala:\s*(?P<customer>.+?)\s*,\s*Kiasi:\s*Tsh\s+(?P<amount>[\d,]+(?:\.\d+)?)\.\s*Salio\s+Jipya\s+ni\s+Tsh\s+(?P<balance>[\d,]+(?:\.\d+)?)\.\s*TxnID:\s*(?P<ref>[A-Z0-9]+)\.\s*(?P<date>\d{1,2}\/\d{1,2}\/\d{2})\s+(?P<time>\d{1,2}:\d{2})/is',
            ],
            'tigo_send' => [
                'type' => 'send_money',
                'pattern' => '/Tigo[^\n]*?:?\s*Umetuma\s+TZS\s+(?P<amount>[\d,]+(?:\.\d+)?)\s+kwa\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b.*?Transaction\s+ID\s*[:\-]\s*(?P<ref>[A-Z0-9]{5,20})\.?\s*(?:Salio\s+lako\s+ni\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
        ];
    }
}
