<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Discount Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the user discounts
    | package including stacking order, caps, and rounding rules.
    |
    */

    'stacking' => [
        'enabled' => true,
        'order' => [
            'percentage' => 1,
            'fixed' => 2,
            'buy_x_get_y' => 3,
        ],
    ],

    'caps' => [
        'max_percentage' => 100,
        'max_fixed_amount' => 1000,
    ],

    'rounding' => [
        'method' => 'round', // round, floor, ceil
        'precision' => 2,
    ],

    'concurrency' => [
        'lock_timeout' => 30, // seconds
        'retry_attempts' => 3,
    ],

    'audit' => [
        'enabled' => true,
        'retention_days' => 365,
    ],
];
