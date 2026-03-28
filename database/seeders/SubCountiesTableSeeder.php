<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\County;
use App\Models\SubCounty;
use Illuminate\Database\Seeder;

class SubCountiesTableSeeder extends Seeder
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

        foreach (config('locations.kenya_sub_counties', []) as $countyName => $subCounties) {
            $county = County::query()->firstOrCreate(
                [
                    'country_id' => $kenya->id,
                    'name' => $countyName,
                ],
                [
                    'is_active' => true,
                ]
            );

            $rows = collect($subCounties)
                ->filter()
                ->map(fn (string $subCountyName) => [
                    'county_id' => $county->id,
                    'name' => $subCountyName,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

            if ($rows === []) {
                continue;
            }

            SubCounty::query()->upsert(
                $rows,
                ['county_id', 'name'],
                ['is_active', 'updated_at']
            );
        }
    }
}
