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
        $colors = [
            'Red', 'Green', 'Blue', 'Yellow', 'Purple', 'Orange', 'Pink', 'Brown',
            'Black', 'White', 'Grey', 'Multi',
            'Navy', 'Maroon', 'Beige', 'Cream', 'Teal', 'Turquoise', 'Magenta', 'Cyan',
            'Olive', 'Khaki', 'Lavender', 'Gold', 'Silver', 'Charcoal', 'Tan', 'Mustard',
            'Burgundy', 'Peach', 'Coral', 'Mint', 'Lime', 'Rust', 'Ivory',
            'Azure', 'Blush', 'Bronze', 'Camel', 'Chocolate', 'Copper', 'Denim', 'Emerald',
            'Fuchsia', 'Indigo', 'Jade', 'Lemon', 'Lilac', 'Mauve', 'Ochre', 'Onyx',
            'Plum', 'Rose', 'Ruby', 'Saffron', 'Sage', 'Sand', 'Scarlet', 'Slate',
            'Taupe', 'Topaz', 'Violet', 'Wine', 'Neon', 'Pastel',
        ];

        foreach ($colors as $colorName) {
            Color::firstOrCreate(
                ['name' => $colorName],
                ['status' => 1]
            );
        }
    }
}
