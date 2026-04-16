<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_order_show_page_renders_order_details(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrderFixture();

        $this->actingAs($admin, 'admin')
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee($order->recipient_name)
            ->assertSee('Order Overview');
    }

    public function test_admin_can_update_order_tracking_status_and_create_a_log(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrderFixture();
        $status = OrderStatus::query()->create([
            'name' => 'Shipped',
            'status' => 1,
            'sort' => 1,
        ]);

        $this->actingAs($admin, 'admin')
            ->from(route('orders.show', $order))
            ->post(route('orders.update-status', $order), [
                'order_status_id' => $status->id,
                'tracking_number' => 'TRACK-1001',
                'tracking_link' => 'https://carrier.example.test/track/TRACK-1001',
                'shipping_partner' => 'Courier Test',
                'remarks' => 'Dispatched from warehouse.',
            ])
            ->assertRedirect(route('orders.show', $order, false))
            ->assertSessionHas('success_message');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'shipped',
            'tracking_number' => 'TRACK-1001',
            'tracking_link' => 'https://carrier.example.test/track/TRACK-1001',
            'shipping_partner' => 'Courier Test',
        ]);

        $this->assertDatabaseHas('order_logs', [
            'order_id' => $order->id,
            'order_status_id' => $status->id,
            'tracking_number' => 'TRACK-1001',
            'tracking_link' => 'https://carrier.example.test/track/TRACK-1001',
            'shipping_partner' => 'Courier Test',
            'remarks' => 'Dispatched from warehouse.',
            'updated_by' => $admin->id,
        ]);
    }

    public function test_admin_can_update_order_and_payment_status_from_manage_order_form(): void
    {
        $admin = $this->createAdmin();
        $order = $this->createOrderFixture();

        $this->actingAs($admin, 'admin')
            ->from(route('orders.show', $order))
            ->patch(route('orders.update', $order), [
                'order_status' => 'confirmed',
                'payment_status' => 'paid',
            ])
            ->assertRedirect(route('orders.show', $order, false))
            ->assertSessionHas('success_message');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    private function createAdmin(): Admin
    {
        return Admin::query()->forceCreate([
            'name' => 'Orders Admin',
            'role' => 'admin',
            'mobile' => '0700001111',
            'email' => 'orders-admin@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
        ]);
    }

    private function createOrderFixture(): Order
    {
        $user = User::query()->create([
            'name' => 'Admin Order User',
            'email' => 'admin-order-user@example.com',
            'password' => 'secret123',
            'status' => 1,
            'phone' => '0711222333',
            'country' => 'Kenya',
        ]);

        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Primary Address',
            'full_name' => 'Admin Order User',
            'phone' => '0711222333',
            'address_line1' => '44 Admin Street',
            'address_line2' => 'Suite 4',
            'country' => 'Kenya',
            'county' => 'Nairobi',
            'sub_county' => 'Westlands',
            'estate' => 'Parklands',
            'landmark' => 'Near the roundabout',
            'pincode' => '00100',
            'is_default' => true,
        ]);

        $order = Order::query()->create([
            'user_id' => $user->id,
            'user_address_id' => $address->id,
            'order_uuid' => (string) Str::uuid(),
            'order_number' => 'SHR-ADMIN-1001',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'placed',
            'currency' => 'KSH',
            'items_count' => 1,
            'subtotal_amount' => 4500,
            'discount_amount' => 0,
            'wallet_applied_amount' => 0,
            'shipping_amount' => 150,
            'grand_total' => 4650,
            'address_label' => 'Primary Address',
            'recipient_name' => 'Admin Order User',
            'recipient_phone' => '0711222333',
            'email' => $user->email,
            'country' => 'Kenya',
            'county' => 'Nairobi',
            'sub_county' => 'Westlands',
            'address_line1' => '44 Admin Street',
            'address_line2' => 'Suite 4',
            'estate' => 'Parklands',
            'landmark' => 'Near the roundabout',
            'pincode' => '00100',
            'shipping_zone' => 'Metro',
            'shipping_eta' => '1-2 business days',
            'shipping_quote' => ['serviceable' => true, 'shipping_amount' => 150],
            'placed_at' => now(),
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name' => 'Admin Test Bag',
            'product_code' => 'ADMIN-ORDER-001',
            'product_url' => 'admin-test-bag',
            'product_image' => asset('front/images/products/no-image.jpg'),
            'size' => 'M',
            'color' => 'Black',
            'quantity' => 1,
            'unit_price' => 4500,
            'line_total' => 4500,
        ]);

        return $order;
    }
}
