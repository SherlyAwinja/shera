<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CartWalletFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_apply_wallet_credit_and_preview_partial_gateway_amount(): void
    {
        $user = User::factory()->create([
            'status' => 1,
        ]);

        $productId = $this->createProduct([
            'product_name' => 'Wallet Partial Product',
            'product_url' => 'wallet-partial-product',
            'product_code' => 'WALLET-PARTIAL-001',
            'group_code' => 'wallet-partial-group',
            'product_price' => 120,
            'final_price' => 120,
        ]);

        Cart::query()->create([
            'session_id' => 'wallet-partial-session',
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_size' => 'NA',
            'product_qty' => 1,
        ]);

        $this->createWalletEntry($user, [
            'action' => Wallet::ACTION_CREDIT,
            'amount' => 80,
            'signed_amount' => 80,
            'description' => 'Available wallet credit',
            'status' => true,
            'expiry_date' => now()->addMonth()->toDateString(),
        ]);

        $this->createWalletEntry($user, [
            'action' => Wallet::ACTION_CREDIT,
            'amount' => 40,
            'signed_amount' => 40,
            'description' => 'Inactive wallet credit',
            'status' => false,
            'expiry_date' => now()->addMonth()->toDateString(),
        ]);

        $this->createWalletEntry($user, [
            'action' => Wallet::ACTION_CREDIT,
            'amount' => 25,
            'signed_amount' => 25,
            'description' => 'Expired wallet credit',
            'status' => true,
            'expiry_date' => now()->subDay()->toDateString(),
        ]);

        $applyResponse = $this->actingAs($user, 'web')
            ->withSession(['session_id' => 'wallet-partial-session'])
            ->postJson(route('cart.apply.wallet'), [
                'wallet_amount' => 200,
            ]);

        $applyResponse
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $this->assertSame(80.0, (float) $applyResponse->json('cart.wallet_balance'));
        $this->assertSame(80.0, (float) $applyResponse->json('cart.wallet_applied'));
        $this->assertSame(40.0, (float) $applyResponse->json('cart.remaining_payable'));

        $this->assertStringContainsString(
            'Wallet credit adjusted to KSH.80.00',
            (string) $applyResponse->json('message')
        );

        $previewResponse = $this->actingAs($user, 'web')
            ->postJson(route('cart.checkout.preview'));

        $previewResponse
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $this->assertSame(80.0, (float) $previewResponse->json('cart.wallet_applied'));
        $this->assertSame(40.0, (float) $previewResponse->json('cart.remaining_payable'));

        $this->assertStringContainsString(
            'The remaining KSH.40.00 should be collected by your payment gateway.',
            (string) $previewResponse->json('message')
        );
    }

    public function test_wallet_only_checkout_creates_a_debit_entry_and_clears_the_cart(): void
    {
        $user = User::factory()->create([
            'status' => 1,
        ]);

        $productId = $this->createProduct([
            'product_name' => 'Wallet Full Product',
            'product_url' => 'wallet-full-product',
            'product_code' => 'WALLET-FULL-001',
            'group_code' => 'wallet-full-group',
            'product_price' => 120,
            'final_price' => 120,
        ]);

        $cartLine = Cart::query()->create([
            'session_id' => 'wallet-full-session',
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_size' => 'NA',
            'product_qty' => 1,
        ]);

        $this->createWalletEntry($user, [
            'action' => Wallet::ACTION_CREDIT,
            'amount' => 150,
            'signed_amount' => 150,
            'description' => 'Wallet checkout credit',
            'status' => true,
            'expiry_date' => now()->addMonth()->toDateString(),
        ]);

        $this->actingAs($user, 'web')
            ->withSession(['session_id' => 'wallet-full-session'])
            ->postJson(route('cart.apply.wallet'), [
                'wallet_amount' => 120,
            ])
            ->assertOk();

        $checkoutResponse = $this->actingAs($user, 'web')
            ->postJson(route('cart.complete-wallet-checkout'));

        $checkoutResponse
            ->assertOk()
            ->assertJson([
                'status' => true,
                'completed_with_wallet' => true,
            ]);

        $this->assertSame(0.0, (float) $checkoutResponse->json('cart.wallet_applied'));
        $this->assertSame(0.0, (float) $checkoutResponse->json('cart.total'));

        $this->assertStringContainsString(
            'Order completed using wallet credit.',
            (string) $checkoutResponse->json('message')
        );

        $this->assertDatabaseMissing('carts', [
            'id' => $cartLine->id,
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'action' => Wallet::ACTION_DEBIT,
            'amount' => 120.00,
            'signed_amount' => -120.00,
            'status' => 1,
        ]);
    }

    public function test_cart_page_points_wallet_usage_to_checkout_for_authenticated_users(): void
    {
        $user = User::factory()->create([
            'status' => 1,
        ]);

        $productId = $this->createProduct([
            'product_name' => 'Wallet Page Product',
            'product_url' => 'wallet-page-product',
            'product_code' => 'WALLET-PAGE-001',
            'group_code' => 'wallet-page-group',
            'product_price' => 90,
            'final_price' => 90,
        ]);

        Cart::query()->create([
            'session_id' => 'wallet-page-session',
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_size' => 'NA',
            'product_qty' => 1,
        ]);

        $this->createWalletEntry($user, [
            'action' => Wallet::ACTION_CREDIT,
            'amount' => 75,
            'signed_amount' => 75,
            'description' => 'Cart page credit',
            'status' => true,
            'expiry_date' => now()->addMonth()->toDateString(),
        ]);

        $this->actingAs($user, 'web')
            ->withSession(['session_id' => 'wallet-page-session'])
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSeeText('Proceed to Checkout')
            ->assertSeeText('Payment method and wallet credit now live in checkout beside delivery details.')
            ->assertDontSeeText('Wallet Credit')
            ->assertDontSeeText('Use Full Available Wallet');
    }

    public function test_account_page_displays_live_wallet_balance_and_recent_entries(): void
    {
        $user = User::factory()->create([
            'status' => 1,
        ]);

        $this->createWalletEntry($user, [
            'action' => Wallet::ACTION_CREDIT,
            'amount' => 100,
            'signed_amount' => 100,
            'description' => 'Promotional credit',
            'status' => true,
            'expiry_date' => now()->addMonth()->toDateString(),
        ]);

        $this->createWalletEntry($user, [
            'action' => Wallet::ACTION_DEBIT,
            'amount' => 25,
            'signed_amount' => -25,
            'description' => 'Wallet checkout debit',
            'status' => true,
            'expiry_date' => null,
        ]);

        $this->actingAs($user, 'web')
            ->get(route('user.account'))
            ->assertOk()
            ->assertSeeText('Wallet Balance')
            ->assertSeeText('KSH.75.00')
            ->assertSeeText('Wallet Activity')
            ->assertSeeText('Recent Wallet Entries')
            ->assertSeeText('Promotional credit')
            ->assertSeeText('Wallet checkout debit');
    }

    private function createWalletEntry(User $user, array $attributes = []): Wallet
    {
        return Wallet::query()->create(array_merge([
            'user_id' => $user->id,
            'action' => Wallet::ACTION_CREDIT,
            'amount' => 10,
            'signed_amount' => 10,
            'description' => 'Seeded wallet entry',
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => true,
        ], $attributes));
    }

    private function createProduct(array $attributes = []): int
    {
        $categoryId = $this->createCategory();

        return (int) DB::table('products')->insertGetId(array_merge([
            'category_id' => $categoryId,
            'brand_id' => null,
            'admin_id' => 1,
            'admin_type' => 'admin',
            'product_name' => 'Wallet Test Product',
            'product_url' => 'wallet-test-product-' . fake()->unique()->slug(),
            'product_code' => 'WALLET-TEST-' . strtoupper(fake()->unique()->bothify('###')),
            'product_color' => 'Black',
            'group_code' => 'wallet-test-group',
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
        ], $attributes));
    }

    private function createCategory(): int
    {
        return (int) DB::table('categories')->insertGetId([
            'parent_id' => 0,
            'name' => 'Wallet Test Category ' . fake()->unique()->words(2, true),
            'image' => null,
            'size_chart' => null,
            'discount' => 0,
            'description' => null,
            'url' => 'wallet-test-category-' . fake()->unique()->slug(),
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'menu_status' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
