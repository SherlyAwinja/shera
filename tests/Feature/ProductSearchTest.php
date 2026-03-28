<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_endpoint_returns_matching_product_markup(): void
    {
        $this->createProduct([
            'product_name' => 'Search Test Tote',
            'product_url' => 'search-test-tote',
            'product_code' => 'SEARCH-TEST-001',
            'search_keywords' => 'luxury tote everyday bag',
            'stock' => 8,
            'status' => 1,
        ]);

        $response = $this->get(route('search.products', ['q' => 'luxury']));

        $response
            ->assertOk()
            ->assertSee('Search Test Tote', false)
            ->assertSee('search-test-tote', false);
    }

    public function test_search_endpoint_returns_no_results_message_for_unmatched_queries(): void
    {
        $response = $this->get(route('search.products', ['q' => 'wallet']));

        $response
            ->assertOk()
            ->assertSee('No products found.', false);
    }

    private function createProduct(array $overrides = []): int
    {
        return (int) DB::table('products')->insertGetId(array_merge([
            'category_id' => 1,
            'brand_id' => null,
            'admin_id' => 1,
            'admin_type' => 'admin',
            'product_name' => 'Search Test Product',
            'product_url' => 'search-test-product',
            'product_code' => 'SEARCH-BASE-001',
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
            'stock' => 10,
            'sort' => 0,
            'main_image' => null,
            'product_video' => null,
            'description' => null,
            'search_keywords' => 'search sample bag',
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
