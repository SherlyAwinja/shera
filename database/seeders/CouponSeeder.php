<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use Carbon\Carbon;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        Coupon::upsert([
            [
                'coupon_option' => 'Manual',
                'coupon_code' => 'WELCOME10',
                'categories' => null,
                'brands' => null,
                'users' => null,
                'coupon_type' => 'Multiple',
                'amount_type' => 'Percentage',
                'amount' => 10.00,
                'min_qty' => null,
                'max_qty' => null,
                'min_cart_value' => 0,
                'max_cart_value' => 10000,
                'total_usage_limit' => 0,
                'usage_limit_per_user' => 0,
                'max_discount' => null,
                'used_count' => 0,
                'expiry_date' => Carbon::now()->addMonths(1)->toDateString(),
                'status' => 1,
                'visible' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'coupon_option' => 'Manual',
                'coupon_code' => 'FLAT500',
                'categories' => json_encode([1, 2]), // Example category IDs
                'brands' => null,
                'users' => json_encode(['test@example.com']), // Example user email
                'coupon_type' => 'Single',
                'amount_type' => 'Fixed',
                'amount' => 500.00,
                'min_qty' => 1,
                'max_qty' => 100,
                'min_cart_value' => 2000.00,
                'max_cart_value' => 20000.00,
                'usage_limit_per_user' => 1,
                'total_usage_limit' => 100,
                'max_discount' => null,
                'used_count' => 0,
                'expiry_date' => Carbon::now()->addMonths(2)->toDateString(),
                'status' => 1,
                'visible' => 0,
                'created_at' => $now,
                'updated_at' => $now,

            ],
        ], ['coupon_code'], [
            'coupon_option',
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
            'usage_limit_per_user',
            'total_usage_limit',
            'max_discount',
            'used_count',
            'expiry_date',
            'status',
            'visible',
            'updated_at',
        ]);
    }
}
