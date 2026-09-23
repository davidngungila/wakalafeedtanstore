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
        'M-PESA' => 'VODACOM',
        'AIRTEL' => 'AIRTEL',
        'Airtel Money' => 'AIRTEL',
        'AIRTELMONEY' => 'AIRTEL',
        'MIXX' => 'MIXX',
        'MIXT' => 'MIXX',
        'YAS' => 'MIXX',
        'HALOPESA' => 'HALOPESA',
        'HaloPesa' => 'HALOPESA',
        'HALO PESA' => 'HALOPESA',
        'TIGO PESA' => 'TIGOPESA',
        'TIGO' => 'TIGOPESA',
        'TIGOPESA' => 'TIGOPESA',
        'TTCL' => 'TTCL',
        'TTCL PESA' => 'TTCL',
        'TTCLPESA' => 'TTCL',
        'AZAM' => 'AZAM',
        'AZAMPESA' => 'AZAM',
        'AZAM PESA' => 'AZAM',
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider parsers
    |--------------------------------------------------------------------------
    | Routes each SMS to a provider-specific parser class in
    | app/Services/Parsers/ based on the sender keyword. The recipient/handset
    | never depends on a single SMS format: an unrecognised sender falls
    | through to the Generic parser, and a provider whose format changes is
    | fixed inside its own parser without touching the others.
    */
    'providers' => [
        'mpesa' => [
            'label' => 'M-Pesa',
            'senders' => ['MPESA', 'M-PESA', 'VODACOM'],
        ],
        'airtel' => [
            'label' => 'Airtel Money',
            'senders' => ['AIRTEL MONEY', 'AIRTELMONEY', 'AIRTEL'],
        ],
        'tigo' => [
            'label' => 'Tigo Pesa',
            'senders' => ['TIGO PESA', 'TIGOPESA', 'TIGO'],
        ],
        'halopesa' => [
            'label' => 'HaloPesa',
            'senders' => ['HALOPESA', 'HALO PESA'],
        ],
        'mixx' => [
            'label' => 'Mixx by Yas',
            'senders' => ['MIXX BY YAS', 'MIXX', 'MIXT', 'YAS'],
        ],
        'ttcl' => [
            'label' => 'TTCL Pesa',
            'senders' => ['TTCL PESA', 'TTCLPESA', 'TTCL'],
        ],
        'azam' => [
            'label' => 'AzamPesa',
            'senders' => ['AZAMPESA', 'AZAM PESA', 'AZAM'],
        ],
        'bank' => [
            'label' => 'Bank SMS',
            'senders' => ['CRDB', 'NMB', 'NBC', 'BANK', 'DIB', 'CHANGO'],
        ],
        'generic' => [
            'label' => 'Generic',
            'senders' => [],
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
