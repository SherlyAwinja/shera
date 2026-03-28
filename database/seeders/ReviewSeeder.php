<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample reviews
        Review::create([
            'product_id' => 1,
            'user_id' => 1,
            'rating' => 5,
            'review' => 'Excellent product! Highly recommend.',
            'status' => 1, // approved
        ]);

        Review::create([
            'product_id' => 1,
            'user_id' => 2,
            'rating' => 4,
            'review' => 'Good quality, but a bit expensive.',
            'status' => 1, // approved
        ]);

        Review::create([
            'product_id' => 2,
            'user_id' => 3,
            'rating' => 3,
            'review' => 'Average product. Not bad, but not great either.',
            'status' => 1, // approved
        ]);

        Review::create([
            'product_id' => 2,
            'user_id' => 4,
            'rating' => 2,
            'review' => 'Not satisfied with the quality.',
            'status' => 0, // pending
        ]);
    }
}
