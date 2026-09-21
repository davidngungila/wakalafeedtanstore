<?php

namespace App\Services\Sms\Parsers;

class TigoParser
{
    public function templates(): array
    {
        return [
            'tigo_deposit_sw' => [
                'type' => 'deposit',
                'pattern' => '/Tigo[^\n]*?:?\s*Umepokea\s+TZS\s+(?P<amount>[\d,]+(?:\.\d+)?)\s+kutoka\s+kwa\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b.*?Transaction\s+ID\s*[:\-]\s*(?P<ref>[A-Z0-9]{5,20})\.?\s*(?:Salio\s+lako\s+ni\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
            'tigo_deposit_en' => [
                'type' => 'deposit',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:]+.*?confirmed.*?You have received\s+TZS\s+(?P<amount>[\d,]+(?:\.\d+)?)\s+from\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b/is',
            ],
            'tigo_send' => [
                'type' => 'send_money',
                'pattern' => '/Tigo[^\n]*?:?\s*Umetuma\s+TZS\s+(?P<amount>[\d,]+(?:\.\d+)?)\s+kwa\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b.*?Transaction\s+ID\s*[:\-]\s*(?P<ref>[A-Z0-9]{5,20})\.?\s*(?:Salio\s+lako\s+ni\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
        ];
    }
}
