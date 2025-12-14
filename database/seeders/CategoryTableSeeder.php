<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use Carbon\Carbon;


class CategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['parent_id' => null, 'name' => 'Handbags', 'url' => 'handbags'],
            ['parent_id' => null, 'name' => 'Travel Bags', 'url' => 'travel-bags'],
            ['parent_id' => null, 'name' => 'Gym Bags', 'url' => 'gym-bags'],
            ['parent_id' => null, 'name' => 'Organizers', 'url' => 'organizers'],
            ['parent_id' => 1, 'name' => 'Tote Bags', 'url' => 'tote-bags'],
            ['parent_id' => 1, 'name' => 'Shoulder Bags', 'url' => 'shoulder-bags'],
            ['parent_id' => 2, 'name' => 'Duffle Bags', 'url' => 'duffle-bags'],
            ['parent_id' => 2, 'name' => 'Carry-on Bags', 'url' => 'carry-on-bags'],
            ['parent_id' => 3, 'name' => 'Yoga Bags', 'url' => 'yoga-bags'],
            ['parent_id' => 3, 'name' => 'Gym Duffel', 'url' => 'gym-duffel'],
            ['parent_id' => 4, 'name' => 'Makeup Bags', 'url' => 'makeup-bags'],
            ['parent_id' => 4, 'name' => 'Lunch Bags', 'url' => 'lunch-bags'],
            ['parent_id' => 5, 'name' => 'Classic Tote Bags', 'url' => 'classic-tote-bags'],
            ['parent_id' => 6, 'name' => 'Hobo Bags', 'url' => 'hobo-bags'],
            ['parent_id' => 9, 'name' => 'Mat Holders', 'url' => 'mat-holders'],
        ];

        foreach($categories as $data) {
            Category::create([
                'parent_id' => $data['parent_id'],
                'name' => $data['name'],
                'url' => $data['url'],
                'image' => '',
                'size_chart' => '',
                'discount' => 0,
                'description' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'status' => 1,
                'menu_status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
