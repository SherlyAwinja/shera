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
            ['parent_id' => null, 'name' => 'Backpacks', 'url' => 'Backpacks'],
            ['parent_id' => null, 'name' => 'Travel Bags', 'url' => 'travel-bags'],
            ['parent_id' => 1, 'name' => 'Tote Bags', 'url' => 'tote-bags'],
            ['parent_id' => 1, 'name' => 'Shoulder Bags', 'url' => 'shoulder-bags'],
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
