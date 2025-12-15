<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductsAttribute;

class ProductsAttributesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productsAttributesRecords = [
            [
                'product_id' => 1,
                'size' => 'Small',
                'sku' => 'ZS001-S',
                'price' => 1500,
                'stock' => 10,
                'sort' => 1,
                'status' => 1,
            ],
            [
                'product_id' => 1,
                'size' => 'Medium',
                'sku' => 'ZS001-M',
                'price' => 1700,
                'stock' => 20,
                'sort' => 2,
                'status' => 1,
            ],
            [
                'product_id' => 1,
                'size' => 'Large',
                'sku' => 'ZS001-L',
                'price' => 1900,
                'stock' => 5,
                'sort' => 3,
                'status' => 1,
            ]
        ];
        ProductsAttribute::insert($productsAttributesRecords);
    }
}
