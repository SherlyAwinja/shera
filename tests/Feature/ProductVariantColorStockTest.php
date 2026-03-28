<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductVariantColorStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_variant_endpoint_returns_stock_for_selected_color(): void
    {
        $productId = $this->createProduct([
            'product_color' => 'Black,Brown',
            'stock' => 11,
            'color_stock' => json_encode([
                'Black' => 4,
                'Brown' => 7,
            ]),
        ]);

        $response = $this->postJson(route('product.variant'), [
            'product_id' => $productId,
            'color' => 'Brown',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => true,
                'color' => 'Brown',
                'stock' => 7,
                'in_stock' => true,
                'can_purchase' => true,
            ]);

        $this->assertStringContainsString('Brown', (string) $response->json('stock_message'));
    }

    private function createProduct(array $overrides = []): int
    {
        return (int) DB::table('products')->insertGetId(array_merge([
            'category_id' => 1,
            'brand_id' => null,
            'admin_id' => 1,
            'admin_type' => 'admin',
            'product_name' => 'Variant Stock Product',
            'product_url' => 'variant-stock-product',
            'product_code' => 'VARIANT-STOCK-001',
            'product_color' => 'Black',
            'group_code' => null,
            'product_price' => 100,
            'product_discount' => 0,
            'product_discount_amount' => 0,
            'discount_applied_on' => 'none',
            'product_gst' => 0,
            'final_price' => 100,
            'material' => null,
            'bag_type' => null,
            'closure_type' => null,
            'strap_type' => null,
            'size' => null,
            'dimensions' => null,
            'compartments' => 0,
            'stock' => 0,
            'color_stock' => null,
            'sort' => 0,
            'main_image' => null,
            'product_video' => null,
            'description' => null,
            'search_keywords' => null,
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'is_featured' => 'No',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
