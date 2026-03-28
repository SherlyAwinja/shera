<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_balance_endpoint_ignores_inactive_and_expired_entries_and_can_exclude_current_entry(): void
    {
        $admin = $this->createAdmin();
        $user = User::query()->create([
            'name' => 'Wallet User',
            'email' => 'wallet-user@example.com',
            'password' => 'secret123',
        ]);

        $this->createWallet($user, ['action' => 'credit', 'amount' => 200, 'signed_amount' => 200, 'status' => true, 'expiry_date' => now()->addMonth()->toDateString()]);
        $this->createWallet($user, ['action' => 'debit', 'amount' => 35, 'signed_amount' => -35, 'status' => true, 'expiry_date' => now()->addMonth()->toDateString()]);
        $this->createWallet($user, ['action' => 'credit', 'amount' => 40, 'signed_amount' => 40, 'status' => false, 'expiry_date' => now()->addMonth()->toDateString()]);
        $this->createWallet($user, ['action' => 'debit', 'amount' => 15, 'signed_amount' => -15, 'status' => true, 'expiry_date' => now()->subDay()->toDateString()]);
        $includedEntry = $this->createWallet($user, ['action' => 'credit', 'amount' => 25, 'signed_amount' => 25, 'status' => true, 'expiry_date' => null]);

        $this->actingAs($admin, 'admin')
            ->getJson(route('wallets.live-balance', ['user_id' => $user->id]))
            ->assertOk()
            ->assertJson([
                'balance' => 190.0,
                'formatted_balance' => 'KES 190.00',
            ]);

        $this->actingAs($admin, 'admin')
            ->getJson(route('wallets.live-balance', ['user_id' => $user->id, 'wallet_id' => $includedEntry->id]))
            ->assertOk()
            ->assertJson([
                'balance' => 165.0,
                'formatted_balance' => 'KES 165.00',
            ]);
    }

    public function test_admin_cannot_store_a_debit_beyond_the_users_live_balance(): void
    {
        $admin = $this->createAdmin();
        $user = User::query()->create([
            'name' => 'Debit Guard User',
            'email' => 'debit-guard@example.com',
            'password' => 'secret123',
        ]);

        $this->createWallet($user, ['action' => 'credit', 'amount' => 50, 'signed_amount' => 50, 'status' => true, 'expiry_date' => now()->addMonth()->toDateString()]);

        $this->actingAs($admin, 'admin')
            ->from(route('wallets.create'))
            ->post(route('wallets.store'), [
                'user_id' => $user->id,
                'action' => 'debit',
                'amount' => 60,
                'description' => 'Attempted overdraft',
                'expiry_date' => now()->addYear()->toDateString(),
                'status' => 1,
            ])
            ->assertRedirect(route('wallets.create'))
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('wallets', [
            'user_id' => $user->id,
            'signed_amount' => -60,
            'description' => 'Attempted overdraft',
        ]);
    }

    public function test_ajax_wallet_status_toggle_returns_recalculated_running_balances(): void
    {
        $admin = $this->createAdmin();
        $user = User::query()->create([
            'name' => 'Toggle Wallet User',
            'email' => 'toggle-wallet@example.com',
            'password' => 'secret123',
        ]);

        $credit = $this->createWallet($user, ['action' => 'credit', 'amount' => 100, 'signed_amount' => 100, 'status' => true, 'expiry_date' => now()->addMonth()->toDateString()]);
        $debit = $this->createWallet($user, ['action' => 'debit', 'amount' => 30, 'signed_amount' => -30, 'status' => true, 'expiry_date' => now()->addMonth()->toDateString()]);
        $inactiveCredit = $this->createWallet($user, ['action' => 'credit', 'amount' => 20, 'signed_amount' => 20, 'status' => false, 'expiry_date' => now()->addMonth()->toDateString()]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('wallets.update-status'), [
                'wallet_id' => $inactiveCredit->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => 1,
                'wallet_id' => $inactiveCredit->id,
                'user_id' => $user->id,
                'user_live_balance' => 90.0,
                'user_live_balance_formatted' => 'KES 90.00',
            ])
            ->assertJsonFragment([
                'id' => $credit->id,
                'running_balance' => 100.0,
                'running_balance_formatted' => 'KES 100.00',
            ])
            ->assertJsonFragment([
                'id' => $debit->id,
                'running_balance' => 70.0,
                'running_balance_formatted' => 'KES 70.00',
            ])
            ->assertJsonFragment([
                'id' => $inactiveCredit->id,
                'running_balance' => 90.0,
                'running_balance_formatted' => 'KES 90.00',
            ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $inactiveCredit->id,
            'status' => 1,
        ]);
    }

    private function createAdmin(): Admin
    {
        return Admin::query()->forceCreate([
            'name' => 'Main Admin',
            'role' => 'admin',
            'mobile' => '0700000000',
            'email' => 'wallet-admin@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
        ]);
    }

    private function createWallet(User $user, array $attributes = []): Wallet
    {
        return Wallet::query()->create(array_merge([
            'user_id' => $user->id,
            'action' => 'credit',
            'amount' => 10,
            'signed_amount' => 10,
            'description' => 'Seeded wallet entry',
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => true,
        ], $attributes));
    }
}
