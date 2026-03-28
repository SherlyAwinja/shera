<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\County;
use Illuminate\Database\Seeder;

class CountiesTableSeeder extends Seeder
{
    public function run(): void
    {
        $kenya = Country::query()->firstOrCreate(
            ['name' => 'Kenya'],
            [
                'iso_code' => 'KE',
                'is_active' => true,
            ]
        );

        $counties = collect(config('locations.kenya_counties', []))
            ->map(fn (string $countyName) => [
                'country_id' => $kenya->id,
                'name' => $countyName,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ])
            ->all();

        County::query()->upsert(
            $counties,
            ['country_id', 'name'],
            ['is_active', 'updated_at']
        );
    }
}
