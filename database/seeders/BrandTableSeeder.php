<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;
use Carbon\Carbon;



class BrandTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            ['name' => 'Arrow', 'url' => 'arrow'],
            ['name' => 'Gap', 'url' => 'gap'],
            ['name' => 'Lee', 'url' => 'lee'],
            ['name' => 'Monte Carlo', 'url' => 'monte-carlo'],
            ['name' => 'Peter England', 'url' => 'peter-england'],
        ];
    
        foreach ($brands as $data) {
            Brand::create([
                'name' => $data['name'],
                'image' => '',
                'logo' => '',
                'discount' => 0,
                'description' => '',
                'url' => $data['url'],
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }    
    }
}
