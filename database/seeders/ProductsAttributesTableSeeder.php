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
                'sku' => 'MDB-S',
                'price' => 4000,
                'stock' => 10,
                'sort' => 1,
                'status' => 1,
            ],
            [
                'product_id' => 1,
                'size' => 'Medium',
                'sku' => 'MDB-M',
                'price' => 4500,
                'stock' => 20,
                'sort' => 2,
                'status' => 1,
            ],
            [
                'product_id' => 1,
                'size' => 'Large',
                'sku' => 'MDB-L',
                'price' => 5000,
                'stock' => 5,
                'sort' => 3,
                'status' => 1,
            ]
        ];
        ProductsAttribute::insert($productsAttributesRecords);
    }
}
