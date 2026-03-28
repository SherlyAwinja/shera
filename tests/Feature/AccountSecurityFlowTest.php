<?php

namespace Tests\Feature;

use App\Mail\PendingEmailVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountSecurityFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_queues_an_email_change_for_verification(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'current@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web')
            ->from(route('user.account'))
            ->put(route('user.account.update'), [
                'name' => 'Current User',
                'email' => 'new-address@example.com',
                'phone' => '+256700000000',
                'country' => 'Uganda',
                'county' => 'Central Region',
                'sub_county' => 'Kampala Central',
                'address_line1' => 'Plot 10',
                'address_line2' => 'Suite 2',
                'estate' => 'Kololo',
                'landmark' => 'Near city square',
            ])
            ->assertRedirect(route('user.account'))
            ->assertSessionHas('info');

        $user->refresh();

        $this->assertSame('current@example.com', $user->email);
        $this->assertSame('new-address@example.com', $user->pending_email);
        $this->assertNotNull($user->email_change_requested_at);

        Mail::assertSent(PendingEmailVerification::class, function (PendingEmailVerification $mail) {
            return $mail->hasTo('new-address@example.com');
        });
    }

    public function test_signed_pending_email_link_promotes_the_new_email(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'pending_email' => 'verified-new@example.com',
            'email_change_requested_at' => now(),
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute(
            'user.account.email.verify',
            now()->addMinutes(60),
            [
                'user' => $user->id,
                'hash' => sha1('verified-new@example.com'),
            ]
        );

        $this->get($url)
            ->assertRedirect(route('user.login', [], false))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('verified-new@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNull($user->email_change_requested_at);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_authenticated_user_can_resend_a_pending_email_verification(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'current@example.com',
            'pending_email' => 'pending@example.com',
            'email_change_requested_at' => now()->subHour(),
        ]);

        $this->actingAs($user, 'web')
            ->post(route('user.account.email.resend'))
            ->assertRedirect(route('user.account'))
            ->assertSessionHas('info');

        Mail::assertSent(PendingEmailVerification::class, function (PendingEmailVerification $mail) {
            return $mail->hasTo('pending@example.com');
        });
    }

    public function test_authenticated_user_can_change_their_password(): void
    {
        $user = User::factory()->create([
            'password' => 'CurrentPassword123!',
        ]);

        $this->actingAs($user, 'web')
            ->from(route('user.account'))
            ->put(route('user.account.password.update'), [
                'current_password' => 'CurrentPassword123!',
                'new_password' => 'UpdatedPassword123!',
                'new_password_confirmation' => 'UpdatedPassword123!',
            ])
            ->assertRedirect(route('user.account'))
            ->assertSessionHas('password_success');

        $this->assertTrue(Hash::check('UpdatedPassword123!', $user->fresh()->password));
    }

    public function test_password_update_rejects_an_invalid_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'CurrentPassword123!',
        ]);

        $this->actingAs($user, 'web')
            ->from(route('user.account'))
            ->put(route('user.account.password.update'), [
                'current_password' => 'WrongPassword123!',
                'new_password' => 'UpdatedPassword123!',
                'new_password_confirmation' => 'UpdatedPassword123!',
            ])
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrorsIn('passwordUpdate', [
                'current_password',
            ]);
    }
}
