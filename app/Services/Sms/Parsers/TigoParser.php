<?php

namespace App\Services\Sms\Parsers;

class TigoParser
{
    public function templates(): array
    {
        return [
            // Umepokea = you have received -> agent receives float, gives cash (withdrawal perspective per user spec: nimepokea cash-, float+)
            'tigo_received' => [
                'type' => 'withdrawal',
                'pattern' => '/Tigo[^\n]*?:?\s*Umepokea\s+TZS\s+(?P<amount>[\d,]+(?:\.\d+)?)\s+kutoka\s+kwa\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b.*?Transaction\s+ID\s*[:\-]\s*(?P<ref>[A-Z0-9]{5,20})\.?\s*(?:Salio\s+lako\s+ni\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
            ],
            'tigo_deposit_en' => [
                'type' => 'deposit',
                'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[\s:]+.*?confirmed.*?You have received\s+TZS\s+(?P<amount>[\d,]+(?:\.\d+)?)\s+from\s+(?P<customer>.+?)\s+(?P<phone>0\d{9,10})\b/is',
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
