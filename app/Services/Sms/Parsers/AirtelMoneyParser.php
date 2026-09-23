<?php

namespace App\Services\Sms\Parsers;

class AirtelMoneyParser
{
    public function templates(): array
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
            'airtel_sent_with_date' => [
                'type' => 'send_money',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:]+.*?(?:Sent|Transferred|Umetuma)\s+T[Ss][Hh]\s+(?P<amount>[\d,]+(?:\.\d+)?).*?(?:to|kwenda)\s+(?P<customer>.+?)\s+(?P<phone>0?\d{9,10})\b.*?(?:on|tarehe)\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?:at|saa)\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?(?:balance\s+is|Balance)\s+T[Ss][Hh]\s*(?P<balance>[\d,]+(?:\.\d+)?)/isu',
            ],
            'airtel_received_with_date' => [
                'type' => 'withdrawal',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:]+.*?(?:Received|Deposited|Umepokea|Umeweka|Credited)\s+T[Ss][Hh]\s+(?P<amount>[\d,]+(?:\.\d+)?).*?(?:from|kwa)\s+(?P<customer>.+?)\s+(?P<phone>0?\d{9,10})\b.*?(?:on|tarehe)\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?:at|saa)\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?(?:balance\s+is|Balance)\s+T[Ss][Hh]\s*(?P<balance>[\d,]+(?:\.\d+)?)/isu',
            ],
            'airtel_withdrawal_cashout' => [
                'type' => 'withdrawal',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:]+.*?(?:withdrawn|cash\s*out|cashout|Umetoa)\s+T[Ss][Hh]\s+(?P<amount>[\d,]+(?:\.\d+)?).*?(?:from|kwa)\s+(?P<customer>.+?)\s+(?P<phone>0?\d{9,10})\b.*?(?:on|tarehe)\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?:at|saa)\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?(?:balance\s+is|Balance)\s+T[Ss][Hh]\s*(?P<balance>[\d,]+(?:\.\d+)?)/isu',
            ],
            'airtel_float_bank_to_wallet' => [
                'type' => 'bank_to_wallet',
                'pattern' => '/(?:You\s+Received|Received|Umewekwa|Umepokea)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]\s+(?:from|kwa)\s+(?P<phone>\d{6,15})\s*,?\s*(?P<customer>[^\.]{1,60}?)\s*\.?\s*(?:New\s+balance|Salio\s+jipya)\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]?\.?\s*(?:Trans\.?\s*ID|TID)\s*[:#]?\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]*)/isu',
            ],
            'airtel_bill_payment' => [
                'type' => 'bill_payment',
                'pattern' => '/(?:AirtelMoney|Airtel\s*Money)[\s\n]+(?:Paid|Umelipa|Bill\s*Payment)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*T[Ss][Hh]\s+(?:to|kwenda|kwa)\s+(?P<customer>[^\n]{1,60}?)\s*.*?(?:TID|TXN|TNX|Trans\.?\s*ID)\s*[:#]\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]{4,})/isu',
            ],
        ];
    }
}
