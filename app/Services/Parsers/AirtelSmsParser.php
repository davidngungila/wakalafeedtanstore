<?php

namespace App\Services\Parsers;

class AirtelSmsParser extends AbstractSmsParser
{
    protected function providerTemplates(): array
    {
        return [
            'airtel_sent_tid_trailing' => [
                'type' => 'send_money',
                'pattern' => '/(?:AirtelMoney|Airtel\s*Money)[\s\n]+(?:Sent|Transferred|Umetuma)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]\s+(?:to|kwenda)\s+(?P<customer>[A-Z][A-Za-z\'\s.]{1,59}?)\s+(?P<phone>\d{9,10})\b.*?Balance\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh].*?commission\s+before\s+Tax\s+is\s+(?P<commission>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh].*?(?:TID|TXN|TNX|Trans\.?\s*ID)\s*[:#]\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]{4,})/isu',
            ],
            'airtel_received_tid_trailing' => [
                'type' => 'withdrawal',
                'pattern' => '/(?:AirtelMoney|Airtel\s*Money)[\s\n]+(?:Received|Deposited|Umepokea|Umeweka|Credited)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]\s+(?:from|kwa)\s+(?P<customer>[A-Z][A-Za-z\'\s.]{1,59}?)\s+(?P<phone>\d{9,10})\b.*?Balance\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh].*?commission\s+before\s+Tax\s+is\s+(?P<commission>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh].*?(?:TID|TXN|TNX|Trans\.?\s*ID)\s*[:#]\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]{4,})/isu',
            ],
            'airtel_float' => [
                'type' => 'bank_to_wallet',
                'pattern' => '/You Received\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*Tsh\s+from\s+(?P<phone>\d{6,15})\s*,?\s*(?P<customer>[^\.]+?)\s*\.?\s*New balance\s+(?P<balance>[\d,]+(?:\.\d+)?)\s*Tsh\.?\s*Trans\.ID:\s*(?P<ref>[A-Z0-9\.\-]+)/is',
            ],
        ];
    }
}
