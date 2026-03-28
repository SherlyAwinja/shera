<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\User;
use App\Mail\UserRegistered;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class FrontAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_auth_routes_are_registered(): void
    {
        $this->assertSame('/user/login', route('user.login', [], false));
        $this->assertSame('/user/register', route('user.register', [], false));
        $this->assertSame('/user/password/forgot', route('user.password.forgot', [], false));
        $this->assertSame(
            '/user/password/reset/sample-token?email=jane%40example.com',
            route('password.reset', ['token' => 'sample-token', 'email' => 'jane@example.com'], false)
        );
    }

    public function test_register_creates_a_user_logs_them_in_and_redirects_home(): void
    {
        Mail::fake();

        $response = $this->postJson(route('user.register.post'), [
            'name' => 'Jane Vendor',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'user_type' => 'Vendor',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful.',
                'redirect' => route('home', [], false),
            ]);

        $user = User::first();

        $this->assertNotNull($user);
        $this->assertSame('Vendor', $user->user_type);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'user_type' => 'Vendor',
            'status' => 1,
        ]);

        Mail::assertSent(UserRegistered::class);
    }

    public function test_login_migrates_guest_cart_items_and_merges_duplicate_lines(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
            'user_type' => 'Customer',
            'status' => 1,
        ]);

        $firstProductId = $this->createProduct([
            'product_name' => 'Login Merge Product One',
            'product_url' => 'login-merge-product-one',
            'product_code' => 'LOGIN-MERGE-001',
            'group_code' => 'login-merge-group-one',
        ]);

        $secondProductId = $this->createProduct([
            'product_name' => 'Login Merge Product Two',
            'product_url' => 'login-merge-product-two',
            'product_code' => 'LOGIN-MERGE-002',
            'group_code' => 'login-merge-group-two',
        ]);

        Cart::create([
            'session_id' => 'existing-user-session',
            'user_id' => $user->id,
            'product_id' => $firstProductId,
            'product_size' => 'M',
            'product_qty' => 2,
        ]);

        Cart::create([
            'session_id' => 'guest-cart-session',
            'user_id' => 0,
            'product_id' => $firstProductId,
            'product_size' => 'M',
            'product_qty' => 3,
        ]);

        Cart::create([
            'session_id' => 'guest-cart-session',
            'user_id' => 0,
            'product_id' => $secondProductId,
            'product_size' => 'L',
            'product_qty' => 1,
        ]);

        $response = $this->withSession(['session_id' => 'guest-cart-session'])->postJson(route('user.login.post'), [
            'email' => $user->email,
            'password' => 'Password123!',
            'user_type' => 'Customer',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
                'redirect' => route('home', [], false),
            ]);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $firstProductId,
            'product_size' => 'M',
            'product_qty' => 5,
        ]);
        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $secondProductId,
            'product_size' => 'L',
            'product_qty' => 1,
        ]);
        $this->assertDatabaseMissing('carts', [
            'session_id' => 'guest-cart-session',
            'user_id' => 0,
        ]);
        $this->assertSame(
            1,
            Cart::query()
                ->where('user_id', $user->id)
                ->where('product_id', $firstProductId)
                ->where('product_size', 'M')
                ->count()
        );
    }

    public function test_register_logs_in_and_migrates_guest_cart_items(): void
    {
        Mail::fake();

        $productId = $this->createProduct([
            'product_name' => 'Register Merge Product',
            'product_url' => 'register-merge-product',
            'product_code' => 'REGISTER-MERGE-001',
            'group_code' => 'register-merge-group',
        ]);

        Cart::create([
            'session_id' => 'guest-register-session',
            'user_id' => 0,
            'product_id' => $productId,
            'product_size' => 'NA',
            'product_qty' => 2,
        ]);

        $response = $this->withSession(['session_id' => 'guest-register-session'])->postJson(route('user.register.post'), [
            'name' => 'Cart Register User',
            'email' => 'cart-register@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'user_type' => 'Customer',
        ]);

        $user = User::where('email', 'cart-register@example.com')->first();

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Registration successful.',
                'redirect' => route('home', [], false),
            ]);

        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('carts', [
            'user_id' => $user->id,
            'product_id' => $productId,
            'product_size' => 'NA',
            'product_qty' => 2,
        ]);
        $this->assertDatabaseMissing('carts', [
            'session_id' => 'guest-register-session',
            'user_id' => 0,
            'product_id' => $productId,
            'product_size' => 'NA',
        ]);
    }

    public function test_login_succeeds_for_matching_user_type(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
            'user_type' => 'Customer',
            'status' => 1,
        ]);

        $response = $this->postJson(route('user.login.post'), [
            'email' => $user->email,
            'password' => 'Password123!',
            'user_type' => 'Customer',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
                'redirect' => route('home', [], false),
            ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_the_wrong_user_type(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
            'user_type' => 'Customer',
            'status' => 1,
        ]);

        $response = $this->postJson(route('user.login.post'), [
            'email' => $user->email,
            'password' => 'Password123!',
            'user_type' => 'Vendor',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_login_rejects_inactive_users(): void
    {
        $user = User::factory()->create([
            'password' => 'Password123!',
            'user_type' => 'Customer',
            'status' => 0,
        ]);

        $response = $this->postJson(route('user.login.post'), [
            'email' => $user->email,
            'password' => 'Password123!',
            'user_type' => 'Customer',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Your account is inactive. Please contact support.');

        $this->assertGuest();
    }

    public function test_logout_logs_out_the_authenticated_user(): void
    {
        $user = User::factory()->create([
            'user_type' => 'Customer',
            'status' => 1,
        ]);

        $response = $this
            ->actingAs($user, 'web')
            ->post(route('user.logout'));

        $response->assertRedirect(route('home', [], false));
        $this->assertGuest('web');
    }

    public function test_forgot_password_sends_a_reset_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'status' => 1,
        ]);

        $this->postJson(route('user.password.forgot.post'), [
            'email' => $user->email,
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_reset_password_updates_the_password_and_logs_in_active_users(): void
    {
        $user = User::factory()->create([
            'password' => 'OldPassword123!',
            'status' => 1,
        ]);

        $token = Password::createToken($user);

        $this->postJson(route('user.password.reset.post'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'redirect' => route('home', [], false),
            ]);

        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_reset_password_returns_field_errors_for_an_invalid_token(): void
    {
        $user = User::factory()->create([
            'status' => 1,
        ]);

        $this->postJson(route('user.password.reset.post'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    private function createProduct(array $overrides = []): int
    {
        return (int) DB::table('products')->insertGetId(array_merge([
            'category_id' => 1,
            'brand_id' => null,
            'admin_id' => 1,
            'admin_type' => 'admin',
            'product_name' => 'Auth Flow Product',
            'product_url' => 'auth-flow-product',
            'product_code' => 'AUTH-FLOW-001',
            'product_color' => 'Black',
            'group_code' => 'auth-flow-group',
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
}
