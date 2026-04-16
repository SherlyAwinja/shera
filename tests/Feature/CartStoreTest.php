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
        $productId = $this->createProduct([
            'product_color' => 'Black,Brown',
        ]);
        $this->createVariant($productId, 'M', 'Black', 3);
        $this->createVariant($productId, 'M', 'Brown', 2);

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

    public function test_store_requires_a_specific_color_when_multiple_variant_combinations_exist(): void
    {
        $productId = $this->createProduct([
            'product_color' => 'Black,Brown',
        ]);
        $this->createVariant($productId, 'M', 'Black', 3);
        $this->createVariant($productId, 'M', 'Brown', 2);

        $response = $this->withSession([])->postJson(route('cart.store'), [
            'product_id' => $productId,
            'qty' => 1,
            'size' => 'M',
        ]);

        $response
            ->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => 'Select a color before adding this item to the cart.',
            ]);
    }

    public function test_cart_page_renders_variant_selectors_for_editable_cart_lines(): void
    {
        $productId = $this->createProduct([
            'product_color' => 'Black,Brown',
        ]);
        $this->createVariant($productId, 'M', 'Black', 3);
        $this->createVariant($productId, 'L', 'Brown', 2);

        DB::table('carts')->insert([
            'session_id' => 'cart-variant-page-session',
            'product_id' => $productId,
            'product_size' => 'M',
            'product_color' => 'Black',
            'product_qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['session_id' => 'cart-variant-page-session'])
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('cart-variant-select', false)
            ->assertSeeText('Choose size and color here.');
    }

    public function test_update_can_switch_a_cart_line_to_another_variant_combination(): void
    {
        $productId = $this->createProduct([
            'product_color' => 'Black,Brown',
        ]);
        $this->createVariant($productId, 'M', 'Black', 3);
        $this->createVariant($productId, 'L', 'Brown', 2);

        $cartId = (int) DB::table('carts')->insertGetId([
            'session_id' => 'cart-variant-update-session',
            'product_id' => $productId,
            'product_size' => 'M',
            'product_color' => 'Black',
            'product_qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['session_id' => 'cart-variant-update-session'])
            ->patchJson(route('cart.update', ['cart' => $cartId]), [
                'qty' => 1,
                'size' => 'L',
                'color' => 'Brown',
            ])
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseHas('carts', [
            'id' => $cartId,
            'product_id' => $productId,
            'product_size' => 'L',
            'product_color' => 'Brown',
            'product_qty' => 1,
        ]);
    }

    public function test_update_merges_duplicate_lines_when_switching_to_an_existing_variant(): void
    {
        $productId = $this->createProduct([
            'product_color' => 'Black,Brown',
        ]);
        $this->createVariant($productId, 'M', 'Black', 5);
        $this->createVariant($productId, 'L', 'Brown', 5);

        $sourceCartId = (int) DB::table('carts')->insertGetId([
            'session_id' => 'cart-variant-merge-session',
            'product_id' => $productId,
            'product_size' => 'M',
            'product_color' => 'Black',
            'product_qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $targetCartId = (int) DB::table('carts')->insertGetId([
            'session_id' => 'cart-variant-merge-session',
            'product_id' => $productId,
            'product_size' => 'L',
            'product_color' => 'Brown',
            'product_qty' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['session_id' => 'cart-variant-merge-session'])
            ->patchJson(route('cart.update', ['cart' => $sourceCartId]), [
                'qty' => 1,
                'size' => 'L',
                'color' => 'Brown',
            ])
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $this->assertDatabaseMissing('carts', [
            'id' => $sourceCartId,
        ]);

        $this->assertDatabaseHas('carts', [
            'id' => $targetCartId,
            'product_id' => $productId,
            'product_size' => 'L',
            'product_color' => 'Brown',
            'product_qty' => 3,
        ]);
    }

    private function createProduct(array $overrides = []): int
    {
        return (int) DB::table('products')->insertGetId(array_merge([
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
        ], $overrides));
    }

    private function createVariant(int $productId, string $size, string $color, int $stock): int
    {
        return (int) DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'size' => $size,
            'color' => $color,
            'stock' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
