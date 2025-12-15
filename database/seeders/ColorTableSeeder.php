<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Color;

class ColorTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = ['Red', 'Green', 'Blue', 'Yellow', 'Purple', 'Orange', 'Pink', 'Brown', 
        'Black', 'White', 'Grey', 'Multi'];
        foreach ($colors as $colorName) {
            $color = new Color;
            $color->name = $colorName;
            $color->status = 1;
            $color->save();
        }
    }
}
