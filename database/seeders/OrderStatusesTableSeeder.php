<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderStatusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::parse('2026-04-16 12:00:00');
        $statuses = [
            ['id' => 1, 'name' => 'Payment Captured', 'status' => 1, 'sort' => 0],
            ['id' => 2, 'name' => 'Payment Failed', 'status' => 1, 'sort' => 0],
            ['id' => 3, 'name' => 'Shipped', 'status' => 1, 'sort' => 0],
            ['id' => 4, 'name' => 'Delivered', 'status' => 1, 'sort' => 0],
            ['id' => 5, 'name' => 'pending', 'status' => 1, 'sort' => 0],
            ['id' => 6, 'name' => 'Confirmed', 'status' => 1, 'sort' => 0],
            ['id' => 7, 'name' => 'Cancelled', 'status' => 1, 'sort' => 0],
        ];
        foreach ($statuses as $s) {
            DB::table('order_statuses')->updateOrInsert(
                ['id' => $s['id']],
                array_merge($s, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
