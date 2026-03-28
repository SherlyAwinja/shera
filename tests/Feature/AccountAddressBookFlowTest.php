<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\County;
use App\Models\SubCounty;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAddressBookFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_saved_address_becomes_default_and_syncs_the_profile_address(): void
    {
        $user = User::factory()->create();
        [$kenya, $county] = $this->createKenyaLocation();

        SubCounty::query()->create([
            'county_id' => $county->id,
            'name' => 'Westlands',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'web')
            ->post(route('user.account.addresses.store'), [
                'saved_address_label' => 'Home',
                'saved_address_country' => $kenya->name,
                'saved_address_county' => 'Nairobi City',
                'saved_address_sub_county' => 'Westlands',
                'saved_address_line1' => '1 Riverside Drive',
                'saved_address_line2' => 'Block A',
                'saved_address_estate' => 'Kilimani',
                'saved_address_landmark' => 'Near the mall',
                'saved_address_make_default' => '1',
            ])
            ->assertRedirect(route('user.account'))
            ->assertSessionHas('address_success');

        $address = UserAddress::query()->firstOrFail();

        $this->assertTrue($address->is_default);
        $this->assertSame('Home', $address->label);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Westlands',
            'address_line1' => '1 Riverside Drive',
        ]);
    }

    public function test_saved_address_book_accepts_manual_non_kenyan_locations(): void
    {
        $user = User::factory()->create();

        Country::query()->create([
            'name' => 'Kenya',
            'iso_code' => 'KE',
            'is_active' => true,
        ]);

        Country::query()->create([
            'name' => 'Uganda',
            'iso_code' => 'UG',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'web')
            ->post(route('user.account.addresses.store'), [
                'saved_address_label' => 'Kampala Office',
                'saved_address_country' => 'Uganda',
                'saved_address_county' => 'Central Region',
                'saved_address_sub_county' => 'Kampala Central',
                'saved_address_line1' => 'Plot 10 Kampala Road',
                'saved_address_line2' => 'Suite 2',
                'saved_address_estate' => 'Kololo',
                'saved_address_landmark' => 'Near city square',
            ])
            ->assertRedirect(route('user.account'))
            ->assertSessionHas('address_success');

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'label' => 'Kampala Office',
            'country' => 'Uganda',
            'county' => 'Central Region',
            'sub_county' => 'Kampala Central',
        ]);
    }

    public function test_user_can_make_another_saved_address_the_default(): void
    {
        $user = User::factory()->create([
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Westlands',
            'address_line1' => '1 Riverside Drive',
        ]);

        $home = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Westlands',
            'address_line1' => '1 Riverside Drive',
            'is_default' => true,
        ]);

        $office = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Office',
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Starehe',
            'address_line1' => 'Kimathi Street',
            'is_default' => false,
        ]);

        $this->actingAs($user, 'web')
            ->post(route('user.account.addresses.default', ['address' => $office->id]))
            ->assertRedirect(route('user.account'))
            ->assertSessionHas('address_success');

        $this->assertFalse($home->fresh()->is_default);
        $this->assertTrue($office->fresh()->is_default);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'address_line1' => 'Kimathi Street',
            'sub_county' => 'Starehe',
        ]);
    }

    public function test_updating_the_default_saved_address_keeps_the_profile_in_sync(): void
    {
        $user = User::factory()->create();
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

        $address = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Westlands',
            'address_line1' => '1 Riverside Drive',
            'is_default' => true,
        ]);

        $this->actingAs($user, 'web')
            ->put(route('user.account.addresses.update', ['address' => $address->id]), [
                'editing_address_id' => $address->id,
                'saved_address_label' => 'Home Updated',
                'saved_address_country' => $kenya->name,
                'saved_address_county' => 'Nairobi City',
                'saved_address_sub_county' => 'Langata',
                'saved_address_line1' => 'Southern Bypass',
                'saved_address_line2' => 'Gate 4',
                'saved_address_estate' => 'Karen',
                'saved_address_landmark' => 'Near the waterfront',
            ])
            ->assertRedirect(route('user.account'))
            ->assertSessionHas('address_success');

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'label' => 'Home Updated',
            'sub_county' => 'Langata',
            'address_line1' => 'Southern Bypass',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'sub_county' => 'Langata',
            'address_line1' => 'Southern Bypass',
            'estate' => 'Karen',
        ]);
    }

    public function test_deleting_the_default_saved_address_promotes_another_address(): void
    {
        $user = User::factory()->create();

        $default = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Home',
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Westlands',
            'address_line1' => '1 Riverside Drive',
            'is_default' => true,
        ]);

        $fallback = UserAddress::query()->create([
            'user_id' => $user->id,
            'label' => 'Office',
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Starehe',
            'address_line1' => 'Kimathi Street',
            'is_default' => false,
        ]);

        $this->actingAs($user, 'web')
            ->delete(route('user.account.addresses.destroy', ['address' => $default->id]))
            ->assertRedirect(route('user.account'))
            ->assertSessionHas('address_success');

        $this->assertDatabaseMissing('user_addresses', [
            'id' => $default->id,
        ]);

        $this->assertTrue($fallback->fresh()->is_default);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'address_line1' => 'Kimathi Street',
            'sub_county' => 'Starehe',
        ]);
    }

    public function test_saved_address_validation_rejects_a_kenyan_sub_county_from_the_wrong_county(): void
    {
        $user = User::factory()->create();
        $kenya = Country::query()->create([
            'name' => 'Kenya',
            'iso_code' => 'KE',
            'is_active' => true,
        ]);

        $nairobi = County::query()->create([
            'country_id' => $kenya->id,
            'name' => 'Nairobi City',
            'is_active' => true,
        ]);

        $mombasa = County::query()->create([
            'country_id' => $kenya->id,
            'name' => 'Mombasa',
            'is_active' => true,
        ]);

        SubCounty::query()->create([
            'county_id' => $nairobi->id,
            'name' => 'Westlands',
            'is_active' => true,
        ]);

        SubCounty::query()->create([
            'county_id' => $mombasa->id,
            'name' => 'Kisauni',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'web')
            ->from(route('user.account'))
            ->post(route('user.account.addresses.store'), [
                'saved_address_label' => 'Invalid Kenya',
                'saved_address_country' => 'Kenya',
                'saved_address_county' => 'Nairobi City',
                'saved_address_sub_county' => 'Kisauni',
                'saved_address_line1' => '1 Riverside Drive',
            ])
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrorsIn('addressBook', [
                'saved_address_sub_county',
            ]);
    }

    protected function createKenyaLocation(): array
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
