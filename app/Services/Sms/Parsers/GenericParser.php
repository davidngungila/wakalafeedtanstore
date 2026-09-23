<?php

namespace App\Services\Sms\Parsers;

/**
 * Universal template set for unknown / new network senders.
 *
 * Uses broad, language-agnostic (English + Swahili) regex patterns that
 * should match the vast majority of Tanzanian mobile-money confirmation
 * messages, even from networks not explicitly configured (AirtelMoney,
 * M-Pesa, Tigo, HaloPesa, Mixx, AzamPesa, TTCL Pesa, banks, or any new
 * entrant).
 *
 * Patterns are ordered from most-specific (labeled ref, explicit "to/from")
 * to most-general. The first match wins.
 */
class GenericParser
{
    public function templates(): array
    {
        $cur = 'T(?:[Ss][Hh]|[Zz][Ss])';

        return [
            'generic_labeled_ref_sent_to' => [
                'type' => 'send_money',
                'pattern' => "/^(?P<ref>[A-Z0-9]{4,16})[\s:\.]+.*?(?:Sent|Transferred|Umetuma|Withdrawn|Umetoa|Cash\s*out|Paid|Umelipa)\s+$cur\s+(?P<amount>[\d,]+(?:\.\d+)?).*?(?:to|kwenda)\s+(?P<customer>[A-Za-z][^\n]{1,60}?)\s+(?P<phone>0?\d{9,10})\b.*?(?:Balance|Salio)\s+$cur?\s*(?P<balance>[\d,]+(?:\.\d+)?)?.*?(?:commission\s+(?:before\s+Tax\s+)?is|Commission|Preview\s+Commission)\s*[:]?\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*$cur?.*?(?:TID|TXN|TNX|Trans?\.?\s*ID)\s*[:#]?\s*(?P<ref2>[A-Z0-9][A-Z0-9.\-]*)/isu",
            ],
            'generic_labeled_ref_received_from' => [
                'type' => 'deposit',
                'pattern' => "/^(?P<ref>[A-Z0-9]{4,16})[\s:\.]+.*?(?:Received|Deposited|Umepokea|Umeweka|Credited|IMEFANIKIWA)\s+$cur\s+(?P<amount>[\d,]+(?:\.\d+)?).*?(?:from|kwa)\s+(?P<customer>[A-Za-z][^\n]{1,60}?)\s+(?P<phone>0?\d{9,10})\b.*?(?:Balance|Salio)\s+$cur?\s*(?P<balance>[\d,]+(?:\.\d+)?)?.*?(?:commission\s+(?:before\s+Tax\s+)?is|Commission|Preview\s+Commission)\s*[:]?\s*(?P<commission>[\d,]+(?:\.\d+)?)\s*$cur?.*?(?:TID|TXN|TNX|Trans?\.?\s*ID)\s*[:#]?\s*(?P<ref2>[A-Z0-9][A-Z0-9.\-]*)/isu",
            ],
            'generic_action_to_name_phone_tid' => [
                'type' => 'send_money',
                'pattern' => "/(?:Sent|Transferred|Umetuma|Paid|Umelipa)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*$cur\s+(?:to|kwenda)\s+(?P<customer>[A-Z][A-Za-z\'\s.]{1,59}?)\s+(?P<phone>0?\d{8,10})\b.*?(?:Balance|Salio)\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*$cur?.*?commission\s+(?:before\s+Tax\s+)?is\s+(?P<commission>[\d,]+(?:\.\d+)?)\s*$cur?.*?(?:TID|TXN|TNX|Trans?\.?\s*ID)\s*[:#]?\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]{3,})/isu",
            ],
            'generic_action_from_name_phone_tid' => [
                'type' => 'deposit',
                'pattern' => "/(?:Received|Deposited|Umepokea|Umeweka|Credited)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*$cur\s+(?:from|kwa)\s+(?P<customer>[A-Z][A-Za-z\'\s.]{1,59}?)\s+(?P<phone>0?\d{8,10})\b.*?(?:Balance|Salio)\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*$cur?.*?commission\s+(?:before\s+Tax\s+)?is\s+(?P<commission>[\d,]+(?:\.\d+)?)\s*$cur?.*?(?:TID|TXN|TNX|Trans?\.?\s*ID)\s*[:#]?\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]{3,})/isu",
            ],
            'generic_swahili_imefanikiwa' => [
                'type' => 'deposit',
                'pattern' => "/^(?P<ref>[A-Z0-9]{4,16})[\s:\.]+.*?IMEFANIKIWA.*?$cur\s+(?P<amount>[\d,]+(?:\.\d+)?).*?(?:kwa|from)\s+(?P<customer>[A-Za-z][A-Za-z\'\s.]{1,59}?)\s*[\s.(]+(?P<phone>0?\d{9,10})\b.*?(?:tarehe|on)\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?(?:Salio\s+jipya|Balance)\s*[:]?\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*$cur/isu",
            ],
            'generic_leading_ref_action_to' => [
                'type' => 'send_money',
                'pattern' => "/^(?P<ref>[A-Z0-9]{4,16})[\s:\.]+.*?(?:Sent|Transferred|Umetuma|Paid|Umelipa|Withdrawn|Umetoa)\s+$cur\s+(?P<amount>[\d,]+(?:\.\d+)?).*?(?:to|kwenda)\s+(?P<customer>[A-Za-z][^\n]{1,60}?)\s+(?P<phone>0?\d{8,10})\b.*?(?:on|tarehe)\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?:at|saa)\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?(?:balance\s+is|Salio)\s+$cur\s*(?P<balance>[\d,]+(?:\.\d+)?)/isu",
            ],
            'generic_leading_ref_action_from' => [
                'type' => 'deposit',
                'pattern' => "/^(?P<ref>[A-Z0-9]{4,16})[\s:\.]+.*?(?:Received|Deposited|Umepokea|Umeweka|Credited)\s+$cur\s+(?P<amount>[\d,]+(?:\.\d+)?).*?(?:from|kwa)\s+(?P<customer>[A-Za-z][^\n]{1,60}?)\s+(?P<phone>0?\d{8,10})\b.*?(?:on|tarehe)\s+(?P<date>\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})\s+(?:at|saa)\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?).*?(?:balance\s+is|Salio)\s+$cur\s*(?P<balance>[\d,]+(?:\.\d+)?)/isu",
            ],
            'generic_airtime_bill' => [
                'type' => 'airtime',
                'pattern' => "/^(?P<ref>[A-Z0-9]{4,16})[\s:\.]+.*?(?:airtime|dakika|data|bill|lipa)\s+.*?$cur\s+(?P<amount>[\d,]+(?:\.\d+)?).*?(?:to|kwenda|kwa)\s+(?P<customer>[^\n]{1,60}?)\s*.*?(?:TID|TXN|TNX|Trans?\.?\s*ID)\s*[:#]?\s*(?P<ref2>[A-Z0-9][A-Z0-9.\-]*)/isu",
            ],
            'generic_float_bank_transfer' => [
                'type' => 'bank_to_wallet',
                'pattern' => "/(?:You\s+Received|Received|Umewekwa|Umepokea)\s+(?P<amount>[\d,]+(?:\.\d+)?)\s*$cur\s+(?:from|kwa)\s+(?P<phone>\d{6,15})\s*,?\s*(?P<customer>[^\.]{1,60}?)\s*\.?\s*(?:New\s+balance|Salio\s+jipya)\s*(?P<balance>[\d,]+(?:\.\d+)?)\s*$cur?\.?\s*(?:Trans?\.?\s*ID|TID)\s*[:#]?\s*(?P<ref>[A-Z0-9][A-Z0-9.\-]*)/isu",
            ],
        ];
    }
}
