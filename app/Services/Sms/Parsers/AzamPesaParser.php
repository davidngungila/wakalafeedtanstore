<?php

namespace App\Services\Sms\Parsers;

class AzamPesaParser
{
    public function templates(): array
    {
        $cur = 'T(?:[Ss][Hh]|[Zz][Ss])';

        return [
            'azam_deposit_parens' => [
                'type' => 'deposit',
                'pattern' => "/^(?P<ref>AZAM[A-Z0-9]{4,14})[\s:\.]+.*?IMEFANIKIWA.*?$cur\s+(?P<amount>[\d,]+(?:\.\d+)?).*?kwa\s+(?P<customer>.+?)\s+\((?P<phone>0?\d{9,10})\).*?tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*[:]?\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*$cur.*?(?:Malipo|Commission|Ada)\s*[:]?\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*$cur?/isu",
            ],
            'azam_deposit_noparens' => [
                'type' => 'deposit',
                'pattern' => "/^(?P<ref>AZAM[A-Z0-9]{4,14})[\s:\.]+.*?IMEFANIKIWA.*?$cur\s+(?P<amount>[\d,]+(?:\.\d+)?).*?kwa\s+(?P<customer>.+?)\s+(?P<phone>0?\d{9,10})\b.*?tarehe\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?Salio\s+jipya\s*[:]?\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*$cur.*?(?:Malipo|Commission|Ada)\s*[:]?\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*$cur?/isu",
            ],
            'azam_generic_sent' => [
                'type' => 'send_money',
                'pattern' => "/(?:AZAMPESA|AzamPesa)[\s\n]+(?:Sent|Transferred|Umetuma)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*$cur\s+(?:to|kwenda)\s+(?P<customer>[A-Z][A-Za-z\'\s.]{1,59}?)\s+(?P<phone>0?\d{9,10})\b.*?Balance\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*$cur.*?(?:commission\s+before\s+Tax\s+is|Commission|Malipo)\s*[:]?\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*$cur?.*?(?:TID|TXN|TNX|Trans\.?\s*ID)\s*[:#]\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]{3,})/isu",
            ],
            'azam_generic_received' => [
                'type' => 'deposit',
                'pattern' => "/(?:AZAMPESA|AzamPesa)[\s\n]+(?:Received|Umepokea|Umeweka|Credited)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*$cur\s+(?:from|kwa)\s+(?P<customer>[A-Z][A-Za-z\'\s.]{1,59}?)\s+(?P<phone>0?\d{9,10})\b.*?Balance\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*$cur.*?(?:commission\s+before\s+Tax\s+is|Commission|Malipo)\s*[:]?\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*$cur?.*?(?:TID|TXN|TNX|Trans\.?\s*ID)\s*[:#]\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]{3,})/isu",
            ],
        ];
    }
}
