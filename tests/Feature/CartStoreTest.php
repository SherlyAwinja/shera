<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_accepts_string_replace_qty_values(): void
    {
        $productId = $this->createProduct();

        $response = $this->withSession([])->postJson(route('cart.store'), [
            'product_id' => $productId,
            'qty' => 1,
            'size' => 'NA',
            'replace_qty' => 'true',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseHas('carts', [
            'product_id' => $productId,
            'product_size' => 'NA',
            'product_qty' => 1,
        ]);
    }

    public function test_store_persists_the_selected_color_when_provided(): void
    {
        $productId = $this->createProduct();

        $response = $this->withSession([])->postJson(route('cart.store'), [
            'product_id' => $productId,
            'qty' => 1,
            'size' => 'M',
            'color' => 'Black',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseHas('carts', [
            'product_id' => $productId,
            'product_size' => 'M',
            'product_color' => 'Black',
            'product_qty' => 1,
        ]);
    }

    private function createProduct(): int
    {
        return (int) DB::table('products')->insertGetId([
            'category_id' => 1,
            'brand_id' => null,
            'admin_id' => 1,
            'admin_type' => 'admin',
            'product_name' => 'Cart Test Product',
            'product_url' => 'cart-test-product',
            'product_code' => 'CART-TEST-001',
            'product_color' => 'Black',
            'group_code' => 'cart-test-group',
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
            'search_keywords' => null,
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'is_featured' => 'No',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
