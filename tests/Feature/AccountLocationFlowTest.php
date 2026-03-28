<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\County;
use App\Models\SubCounty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountLocationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_location_lookup_routes_are_registered(): void
    {
        $this->assertSame(
            '/user/account/location/counties',
            route('user.account.locations.counties', [], false)
        );

        $this->assertSame(
            '/user/account/location/sub-counties',
            route('user.account.locations.sub-counties', [], false)
        );
    }

    public function test_authenticated_user_can_fetch_kenyan_counties_and_subcounties(): void
    {
        $user = User::factory()->create();
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

        SubCounty::query()->create([
            'county_id' => $county->id,
            'name' => 'Westlands',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'web')
            ->getJson(route('user.account.locations.counties', ['country_id' => $kenya->id]))
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Nairobi City',
            ]);

        $this->actingAs($user, 'web')
            ->getJson(route('user.account.locations.sub-counties', ['county_id' => $county->id]))
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'Westlands',
            ]);
    }

    public function test_account_update_saves_kenyan_dropdown_values(): void
    {
        $user = User::factory()->create([
            'email' => 'kenya@example.com',
        ]);

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

        SubCounty::query()->create([
            'county_id' => $county->id,
            'name' => 'Westlands',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'web')
            ->from(route('user.account'))
            ->put(route('user.account.update'), [
                'name' => 'Kenya Profile',
                'email' => 'kenya@example.com',
                'phone' => '+254700000000',
                'country' => 'Kenya',
                'county' => 'Nairobi City',
                'sub_county' => 'Westlands',
                'address_line1' => '1 Riverside Drive',
                'address_line2' => 'Block A',
                'estate' => 'Kilimani',
                'landmark' => 'Near the mall',
            ])
            ->assertRedirect(route('user.account'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Kenya Profile',
            'country' => 'Kenya',
            'county' => 'Nairobi City',
            'sub_county' => 'Westlands',
        ]);
    }

    public function test_account_update_saves_non_kenyan_text_location_values(): void
    {
        $user = User::factory()->create([
            'email' => 'uganda@example.com',
        ]);

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
            ->from(route('user.account'))
            ->put(route('user.account.update'), [
                'name' => 'Uganda Profile',
                'email' => 'uganda@example.com',
                'phone' => '+256700000000',
                'country' => 'Uganda',
                'county' => 'Central Region',
                'sub_county' => 'Kampala Central',
                'address_line1' => 'Plot 10 Kampala Road',
                'address_line2' => 'Suite 2',
                'estate' => 'Kololo',
                'landmark' => 'Near city square',
            ])
            ->assertRedirect(route('user.account'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'country' => 'Uganda',
            'county' => 'Central Region',
            'sub_county' => 'Kampala Central',
        ]);
    }

    public function test_kenyan_account_update_rejects_a_sub_county_from_the_wrong_county(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid@example.com',
        ]);

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
            ->put(route('user.account.update'), [
                'name' => 'Invalid Kenya Profile',
                'email' => 'invalid@example.com',
                'phone' => '+254700000000',
                'country' => 'Kenya',
                'county' => 'Nairobi City',
                'sub_county' => 'Kisauni',
            ])
            ->assertRedirect(route('user.account'))
            ->assertSessionHasErrors([
                'sub_county',
            ]);
    }
}
