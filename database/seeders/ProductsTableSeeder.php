<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $duffleBagsCategory = Category::where('name', 'Duffle Bags')->first();
        if ($duffleBagsCategory) {
            Product::create ([
                'category_id' => $duffleBagsCategory->id,
                'brand_id' => 1,
                'admin_id' => 1,
                'admin_type' => 'admin',
                'product_name' => 'Men Duffle Bag',
                'product_code' => 'MDB',
                'product_color' => 'Olive',
                'family_color' => 'Green',
                'product_price' => 4500,
                'product_discount' => 10,
                'product_discount_amount' => 450,
                'discount_applied_on' => 'product',
                'product_gst' => 18,
                'final_price' => 4050,
                'main_image' =>'',
                'dimensions' => '40x30x10 cm',
                'product_video' => '',
                'description' => 'Men Duffle Bag is a stylish and functional bag for everyday use. It is made of high quality material and has a spacious main compartment and multiple pockets for storage.',
                'material' => '',
                'bag_type' => '',
                'search_keywords' => '',
                'closure_type' => '',
                'strap_type' => '',
                'compartments' => 0,
                'stock' => 50,
                'sort' => 1,
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'is_featured' => 'No',
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            Product::create ([
                'category_id' => $duffleBagsCategory->id,
                'brand_id' => 1,
                'admin_id' => 1,
                'admin_type' => 'admin',
                'product_name' => 'Women Duffle Bag',
                'product_code' => 'WDB',
                'product_color' => 'Black',
                'family_color' => 'Black',
                'product_price' => 5000,
                'product_discount' => 0,
                'product_discount_amount' => 0,
                'discount_applied_on' => 'product',
                'product_gst' => 12,
                'final_price' => 5000,
                'main_image' =>'',
                'dimensions' => '40x30x10 cm',
                'product_video' => '',
                'description' => 'Women Duffle Bag is a stylish and functional bag for everyday use. It is made of high quality material and has a spacious main compartment and multiple pockets for storage.',
                'material' => '',
                'bag_type' => '',
                'search_keywords' => '',
                'closure_type' => '',
                'strap_type' => '',
                'compartments' => 0,
                'stock' => 10,
                'sort' => 2,
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'is_featured' => 'Yes',
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
