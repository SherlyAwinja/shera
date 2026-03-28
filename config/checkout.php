<?php

return [
    'shipping' => [
        'free_shipping_threshold' => 10000,
        'zones' => [
            [
                'label' => 'Metro',
                'min_prefix' => 0,
                'max_prefix' => 19,
                'amount' => 150,
                'eta' => '1-2 business days',
            ],
            [
                'label' => 'Regional',
                'min_prefix' => 20,
                'max_prefix' => 59,
                'amount' => 300,
                'eta' => '2-4 business days',
            ],
            [
                'label' => 'Extended',
                'min_prefix' => 60,
                'max_prefix' => 89,
                'amount' => 450,
                'eta' => '3-5 business days',
            ],
        ],
    ],
];
