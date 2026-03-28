<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountriesTableSeeder extends Seeder
{
    public function run(): void
    {
        collect(config('locations.country_suggestions', []))
            ->each(function (array $country): void {
                Country::query()->updateOrCreate(
                    ['name' => $country['name']],
                    [
                        'iso_code' => $country['iso_code'] ?? null,
                        'is_active' => true,
                    ]
                );
            });
    }
}
