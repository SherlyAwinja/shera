<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Country;
use App\Models\County;
use App\Models\SubCounty;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_page_renders_saved_addresses_and_order_summary(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Customer',
            'phone' => '0712345678',
            'status' => 1,
        ]);

        $productId = $this->createProduct([
            'product_name' => 'Checkout Summary Product',
            'product_url' => 'checkout-summary-product',
            'product_code' => 'CHECKOUT-SUMMARY-001',
        ]);

        Cart::query()->create([
            'session_id' => 'checkout-summary-session',
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_size' => 'M',
            'product_color' => 'Black',
            'product_qty' => 2,
        ]);

        UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Jane - Westlands',
            'full_name' => 'Jane Customer',
            'phone' => '0712345678',
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Westlands',
            'address_line1' => '1 Riverside Drive',
            'pincode' => '00100',
            'is_default' => true,
        ]);

        $this->actingAs($user, 'web')
            ->withSession(['session_id' => 'checkout-summary-session'])
            ->get(route('user.checkout.index'))
            ->assertOk()
            ->assertSeeText('Order Summary')
            ->assertSeeText('Checkout Summary Product')
            ->assertSeeText('Jane Customer')
            ->assertSeeText('0712345678')
            ->assertSeeText('Proceed to Payment');
    }

    public function test_checkout_can_store_a_new_address_and_return_an_updated_summary_payload(): void
    {
        $user = User::factory()->create([
            'name' => 'Checkout User',
            'phone' => '0799999999',
            'status' => 1,
        ]);

        [$kenya, $county] = $this->createKenyaLocation();

        SubCounty::query()->create([
            'county_id' => $county->id,
            'name' => 'Westlands',
            'is_active' => true,
        ]);

        $productId = $this->createProduct([
            'product_name' => 'Checkout Address Product',
            'product_url' => 'checkout-address-product',
            'product_code' => 'CHECKOUT-ADDRESS-001',
        ]);

        Cart::query()->create([
            'session_id' => 'checkout-address-session',
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_size' => 'NA',
            'product_color' => 'Black',
            'product_qty' => 1,
        ]);

        $response = $this->actingAs($user, 'web')
            ->withSession(['session_id' => 'checkout-address-session'])
            ->postJson(route('user.checkout.addresses'), [
                'full_name' => 'Checkout User',
                'phone' => '0799999999',
                'address_country' => $kenya->name,
                'address_county' => 'Nairobi City',
                'address_sub_county' => 'Westlands',
                'address_line1' => '12 Market Street',
                'address_estate' => 'Parklands',
                'address_landmark' => 'Near the roundabout',
                'address_pincode' => '00100',
                'make_default' => true,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        $this->assertTrue((bool) $response->json('summary.canProceed'));

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'full_name' => 'Checkout User',
            'phone' => '0799999999',
            'pincode' => '00100',
        ]);
    }

    public function test_checkout_summary_marks_unserviceable_addresses_as_not_ready(): void
    {
        $user = User::factory()->create([
            'status' => 1,
        ]);

        $productId = $this->createProduct([
            'product_name' => 'Unserviceable Product',
            'product_url' => 'unserviceable-product',
            'product_code' => 'CHECKOUT-UNSERVICEABLE-001',
        ]);

        Cart::query()->create([
            'session_id' => 'checkout-unserviceable-session',
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_size' => 'NA',
            'product_color' => 'Black',
            'product_qty' => 1,
        ]);

        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Remote',
            'full_name' => 'Remote Customer',
            'phone' => '0700000000',
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Westlands',
            'address_line1' => 'No. 1 Remote Street',
            'pincode' => '99000',
            'is_default' => true,
        ]);

        $response = $this->actingAs($user, 'web')
            ->withSession(['session_id' => 'checkout-unserviceable-session'])
            ->postJson(route('user.checkout.summary'), [
                'address_id' => $address->id,
            ]);

        $response->assertOk();

        $this->assertFalse((bool) $response->json('summary.canProceed'));
        $this->assertSame('danger', $response->json('summary.statusTone'));
        $this->assertStringContainsString(
            'do not currently service deliveries',
            (string) $response->json('summary.statusMessage')
        );
    }

    public function test_checkout_can_update_a_selected_saved_address_and_refresh_the_summary_payload(): void
    {
        $user = User::factory()->create([
            'name' => 'Update Ready User',
            'phone' => '0711111111',
            'status' => 1,
        ]);

        [$kenya, $county] = $this->createKenyaLocation();

        SubCounty::query()->create([
            'county_id' => $county->id,
            'name' => 'Westlands',
            'is_active' => true,
        ]);

        SubCounty::query()->create([
            'county_id' => $county->id,
            'name' => 'Langata',
            'is_active' => true,
        ]);

        $productId = $this->createProduct([
            'product_name' => 'Editable Checkout Product',
            'product_url' => 'editable-checkout-product',
            'product_code' => 'CHECKOUT-EDIT-001',
        ]);

        Cart::query()->create([
            'session_id' => 'checkout-edit-session',
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_size' => 'NA',
            'product_color' => 'Black',
            'product_qty' => 1,
        ]);

        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Home',
            'full_name' => 'Update Ready User',
            'phone' => '0711111111',
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Westlands',
            'address_line1' => '1 Riverside Drive',
            'pincode' => '00100',
            'is_default' => true,
        ]);

        $response = $this->actingAs($user, 'web')
            ->withSession([
                'session_id' => 'checkout-edit-session',
                'checkout_selected_address_id' => $address->id,
            ])
            ->putJson(route('user.checkout.addresses.update', ['address' => $address->id]), [
                'full_name' => 'Updated Checkout User',
                'phone' => '0722222222',
                'address_country' => $kenya->name,
                'address_county' => 'Nairobi City',
                'address_sub_county' => 'Langata',
                'address_line1' => 'Southern Bypass',
                'address_line2' => 'Gate 4',
                'address_estate' => 'Karen',
                'address_landmark' => 'Near the waterfront',
                'address_pincode' => '00200',
                'make_default' => true,
            ]);

        $response
            ->assertOk()
            ->assertJson([
                'status' => true,
                'selected_address_id' => $address->id,
            ]);

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'full_name' => 'Updated Checkout User',
            'phone' => '0722222222',
            'sub_county' => 'Langata',
            'address_line1' => 'Southern Bypass',
            'pincode' => '00200',
        ]);

        $this->assertTrue((bool) $response->json('summary.canProceed'));
    }

    public function test_checkout_deleting_a_selected_address_falls_back_to_the_remaining_default_address(): void
    {
        $user = User::factory()->create([
            'status' => 1,
        ]);

        $productId = $this->createProduct([
            'product_name' => 'Delete Fallback Product',
            'product_url' => 'delete-fallback-product',
            'product_code' => 'CHECKOUT-DELETE-001',
        ]);

        Cart::query()->create([
            'session_id' => 'checkout-delete-fallback-session',
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_size' => 'NA',
            'product_color' => 'Black',
            'product_qty' => 1,
        ]);

        $defaultAddress = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Default',
            'full_name' => 'Default User',
            'phone' => '0700000000',
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Westlands',
            'address_line1' => '1 Riverside Drive',
            'pincode' => '00100',
            'is_default' => true,
        ]);

        $selectedAddress = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Temporary',
            'full_name' => 'Temp User',
            'phone' => '0710000000',
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Starehe',
            'address_line1' => 'Kimathi Street',
            'pincode' => '00100',
            'is_default' => false,
        ]);

        $response = $this->actingAs($user, 'web')
            ->withSession([
                'session_id' => 'checkout-delete-fallback-session',
                'checkout_selected_address_id' => $selectedAddress->id,
            ])
            ->deleteJson(route('user.checkout.addresses.destroy', ['address' => $selectedAddress->id]));

        $response
            ->assertOk()
            ->assertJson([
                'status' => true,
                'selected_address_id' => $defaultAddress->id,
                'address_count' => 1,
            ]);

        $this->assertDatabaseMissing('user_addresses', [
            'id' => $selectedAddress->id,
        ]);
    }

    public function test_checkout_deleting_the_last_saved_address_clears_the_selected_address(): void
    {
        $user = User::factory()->create([
            'status' => 1,
        ]);

        $productId = $this->createProduct([
            'product_name' => 'Delete Last Address Product',
            'product_url' => 'delete-last-address-product',
            'product_code' => 'CHECKOUT-DELETE-002',
        ]);

        Cart::query()->create([
            'session_id' => 'checkout-delete-last-session',
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_size' => 'NA',
            'product_color' => 'Black',
            'product_qty' => 1,
        ]);

        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Only Address',
            'full_name' => 'Solo User',
            'phone' => '0701234567',
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Westlands',
            'address_line1' => '1 Riverside Drive',
            'pincode' => '00100',
            'is_default' => true,
        ]);

        $response = $this->actingAs($user, 'web')
            ->withSession([
                'session_id' => 'checkout-delete-last-session',
                'checkout_selected_address_id' => $address->id,
            ])
            ->deleteJson(route('user.checkout.addresses.destroy', ['address' => $address->id]));

        $response
            ->assertOk()
            ->assertJson([
                'status' => true,
                'selected_address_id' => null,
                'address_count' => 0,
            ]);

        $this->assertDatabaseMissing('user_addresses', [
            'id' => $address->id,
        ]);

        $this->assertSame('info', $response->json('summary.statusTone'));
        $this->assertStringContainsString(
            'Choose a delivery address',
            (string) $response->json('summary.statusMessage')
        );
    }

    public function test_place_order_requires_a_valid_delivery_address(): void
    {
        $user = User::factory()->create([
            'status' => 1,
        ]);

        $productId = $this->createProduct([
            'product_name' => 'Place Order Product',
            'product_url' => 'place-order-product',
            'product_code' => 'CHECKOUT-PLACE-ORDER-001',
        ]);

        Cart::query()->create([
            'session_id' => 'checkout-place-order-session',
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_size' => 'NA',
            'product_qty' => 1,
        ]);

        $this->actingAs($user, 'web')
            ->withSession(['session_id' => 'checkout-place-order-session'])
            ->post(route('user.checkout.placeOrder'), [
                'payment_method' => 'cod',
            ])
            ->assertRedirect(route('user.checkout.index'))
            ->assertSessionHas('checkout_error');
    }

    private function createProduct(array $attributes = []): int
    {
        $categoryId = $this->createCategory();

        return (int) DB::table('products')->insertGetId(array_merge([
            'category_id' => $categoryId,
            'brand_id' => null,
            'admin_id' => 1,
            'admin_type' => 'admin',
            'product_name' => 'Checkout Test Product',
            'product_url' => 'checkout-test-product-' . fake()->unique()->slug(),
            'product_code' => 'CHECKOUT-TEST-' . strtoupper(fake()->unique()->bothify('###')),
            'product_color' => 'Black',
            'group_code' => 'checkout-test-group',
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
            'name' => 'Checkout Test Category ' . fake()->unique()->words(2, true),
            'image' => null,
            'size_chart' => null,
            'discount' => 0,
            'description' => null,
            'url' => 'checkout-test-category-' . fake()->unique()->slug(),
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'menu_status' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createKenyaLocation(): array
    {
        $kenya = Country::query()->create([
            'name' => 'Kenya',
            'iso_code' => 'KE',
            'is_active' => true,
        ]);

        $county = County::query()->create([
            'country_id' => $kenya->id,
            'name' => 'Nairobi City',
            'is_active' => true,
        ]);

        return [$kenya, $county];
    }
}
