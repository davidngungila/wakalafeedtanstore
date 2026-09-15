<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS ingestion
    |--------------------------------------------------------------------------
    | Sender keywords: a best-effort hint mapping the "from" name of an SMS to
    | a network code. Every SMS is accepted regardless of sender; when no
    | keyword matches, the message is attributed to the device's assigned
    | network. The first matching entry wins.
    */
    'senders' => [
        'MPESA' => 'VODACOM',
        'VODACOM' => 'VODACOM',
        'AIRTEL' => 'AIRTEL',
        'Airtel Money' => 'AIRTEL',
        'MIXX' => 'MIXX',
        'MIXT' => 'MIXX',
        'YAS' => 'MIXX',
        'HALOPESA' => 'HALOPESA',
        'HaloPesa' => 'HALOPESA',
    ],

    /*
    |--------------------------------------------------------------------------
    | Parser templates
    |--------------------------------------------------------------------------
    | Generic Tanzanian mobile-money SMS templates. Named captures shared by
    | every template:
    |   ref       leading confirmation code
    |   amount    monetary amount
    |   customer  counterparty name
    |   phone     counterparty number (where available)
    |   date      "22/6/26" or "22-06-2026"
    |   time      "14:21" or "14:21:30"
    |   balance   resulting wallet balance (optional)
    | A message that matches no template is stored as "ignored" and never
    | becomes a transaction.
    */
    'templates' => [
        'deposit' => [
            'type' => 'deposit',
            'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[:\s]+.*?(?:received|deposited|credite?d).+?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).+?from\s+(?P<customer>.+?)\s+(?P<phone>0\d{8,9})\b.*?(?:on\s+(?P<date>\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}))?\s*(?:at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?))?.*?(?:balance is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
        ],
        'withdrawal' => [
            'type' => 'withdrawal',
            'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[:\s]+.*?(?:withdrawn|cash out|cashout).+?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).+?from\s+(?P<customer>.+?)\s+(?P<phone>0\d{8,9})\b.*?(?:on\s+(?P<date>\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}))?\s*(?:at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?))?.*?(?:balance is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
        ],
        'send_money' => [
            'type' => 'send_money',
            'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[:\s]+.*?(?:sent|transferred).*?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).+?to\s+(?P<customer>[^\n]+?)\s+(?P<phone>0\d{8,9})\b.*?(?:on\s+(?P<date>\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}))?\s*(?:at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?))?.*?(?:balance is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
        ],
        'bill_payment' => [
            'type' => 'bill_payment',
            'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[:\s]+.*?(?:paid|payment to|bill\s+pyt).*?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).+?to\s+(?P<customer>[^\n]+?)\s*.*?(?:on\s+(?P<date>\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}))?\s*(?:at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?))?.*?(?:balance is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
        ],
        'airtime' => [
            'type' => 'airtime',
            'pattern' => '/^(?P<ref>[A-Z0-9]{5,12})[:\s]+.*?(?:airtime).*?TZS\s+(?P<amount>[\d,]+(?:\.\d+)?).+?to\s+(?P<customer>[^\n]+?)\s*.*?(?:on\s+(?P<date>\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}))?\s*(?:at\s+(?P<time>\d{1,2}:\d{2}(?::\d{2})?))?.*?(?:balance is\s+TZS\s+(?P<balance>[\d,]+(?:\.\d+)?))?/is',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Device connectivity
    |--------------------------------------------------------------------------
    | An active device whose last heartbeat is older than this threshold is
    | shown as "Offline" on the admin screen.
    */
    'offline_after_minutes' => 10,
];
