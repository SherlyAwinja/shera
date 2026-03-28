<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'coupon_option',
        'coupon_code',
        'categories',
        'brands',
        'users',
        'coupon_type',
        'amount_type',
        'amount',
        'min_qty',
        'max_qty',
        'min_cart_value',
        'max_cart_value',
        'total_usage_limit',
        'usage_limit_per_user',
        'max_discount',
        'expiry_date',
        'status',
        'visible',
        'used_count',
    ];

    protected $casts = [
        'categories' => 'array',
        'brands' => 'array',
        'users' => 'array',
        'amount' => 'float',
        'min_cart_value' => 'float',
        'max_cart_value' => 'float',
        'max_discount' => 'float',
        'expiry_date' => 'date',
    ];
}
