<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
            ]
        );

        $this->call([
            CountriesTableSeeder::class,
            CountiesTableSeeder::class,
            SubCountiesTableSeeder::class,
            AdminsTableSeeder::class,
            CategoryTableSeeder::class,
            ColorTableSeeder::class,
            ProductsAttributesTableSeeder::class,
            BrandTableSeeder::class,
            ProductsTableSeeder::class,
            FiltersTableSeeder::class,
            ReviewSeeder::class,
            CouponSeeder::class,
        ]);
    }
}
