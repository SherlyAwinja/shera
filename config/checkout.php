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
    'payment' => [
        'default_method' => 'cod',
        'methods' => [
            'mobile_wallet' => [
                'label' => 'Mobile Wallet',
                'meta' => 'M-Pesa / Airtel Money',
                'description' => 'Collect payment through supported mobile wallets including M-Pesa and Airtel Money.',
                'icon' => 'fa-sim-card',
            ],
            'paypal' => [
                'label' => 'PayPal',
                'meta' => 'Global wallet',
                'description' => 'Record PayPal as the preferred payment channel for this order.',
                'icon' => 'fa-paypal',
            ],
            'bank_transfer' => [
                'label' => 'Direct Bank Transfer',
                'meta' => 'Bank payment',
                'description' => 'Pay directly to the store bank account and confirm the transfer after ordering.',
                'icon' => 'fa-university',
            ],
            'cod' => [
                'label' => 'Cash on Delivery',
                'meta' => 'COD',
                'description' => 'Pay when the order arrives at the delivery address.',
                'icon' => 'fa-money-bill-wave',
            ],
            'card' => [
                'label' => 'Card Payment',
                'meta' => 'Visa / Mastercard',
                'description' => 'Use a debit or credit card, including Visa and Mastercard.',
                'icon' => 'fa-credit-card',
            ],
            'wallet' => [
                'label' => 'Wallet',
                'meta' => 'Store balance',
                'description' => 'Apply your store wallet balance from checkout and use it as full payment when it covers the order.',
                'icon' => 'fa-wallet',
            ],
        ],
    ],
];
