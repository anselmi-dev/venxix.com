<?php

return [
    'prices' => [
        'free' => [
            'monthly' => 0,
            'yearly' => 0,
        ],
        'pro' => [
            'monthly' => 199,
            'yearly' => 1910,
        ],
        'familiar' => [
            'monthly' => 299,
            'yearly' => 2870,
        ],
    ],

    'reminders' => [
        'days_before' => (int) env('INSTALLMENT_REMINDER_DAYS', 3),
    ],

    'exchange_rates' => [
        'url' => env('EXCHANGE_RATE_URL', 'https://datosuruguay.com/api/v1/exchange-rates/usd'),
    ],

    'billing' => [
        'default_gateway' => env('BILLING_GATEWAY', 'stripe'),
    ],
];
