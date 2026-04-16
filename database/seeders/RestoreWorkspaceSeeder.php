<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RestoreWorkspaceSeeder extends Seeder
{
    private const SNAPSHOT_AT = '2026-04-16 14:42:51';

    public function run(): void
    {
        $this->call([
            CountriesTableSeeder::class,
            CountiesTableSeeder::class,
            SubCountiesTableSeeder::class,
            ColorTableSeeder::class,
        ]);

        $adminIdByEmail = $this->seedAdmins();
        $userIdByEmail = $this->seedUsers();
        $categoryIdByUrl = $this->seedCategories();
        $brandIdByUrl = $this->seedBrands();

        $productIdByCode = $this->seedProducts($categoryIdByUrl, $brandIdByUrl, $adminIdByEmail);

        $this->seedProductAttributes($productIdByCode);
        $this->seedProductVariants($productIdByCode);
        $this->seedProductImages($productIdByCode);
        $this->seedBanners();
        $this->seedCoupons($categoryIdByUrl, $brandIdByUrl);
        $this->seedReviews($productIdByCode, $userIdByEmail);

        $this->call([
            FiltersTableSeeder::class,
            OrderStatusesTableSeeder::class,
        ]);
    }

    private function seedAdmins(): array
    {
        foreach ($this->adminSeedData() as $admin) {
            $existing = DB::table('admins')->where('email', $admin['email'])->first();

            $payload = [
                'name' => $admin['name'],
                'role' => $admin['role'],
                'mobile' => $admin['mobile'],
                'status' => $admin['status'],
            ];

            if (!$existing || empty($existing->password)) {
                $payload['password'] = Hash::make($admin['password']);
            }

            $this->persist('admins', ['email' => $admin['email']], $payload);
        }

        return $this->pluckIdsByKey('admins', 'email', array_column($this->adminSeedData(), 'email'));
    }

    private function seedUsers(): array
    {
        foreach ($this->userSeedData() as $user) {
            $existing = DB::table('users')->where('email', $user['email'])->first();

            $payload = [
                'name' => $user['name'],
                'user_type' => $user['user_type'],
                'status' => $user['status'],
                'country' => $user['country'],
                'county' => $user['county'],
                'sub_county' => $user['sub_county'],
                'phone' => $user['phone'],
                'is_admin' => $user['is_admin'],
            ];

            if (!$existing || empty($existing->password)) {
                $payload['password'] = Hash::make($user['password']);
            }

            $this->persist('users', ['email' => $user['email']], $payload);
        }

        return $this->pluckIdsByKey('users', 'email', array_column($this->userSeedData(), 'email'));
    }

    private function seedCategories(): array
    {
        foreach ($this->categorySeedData() as $category) {
            $this->persist('categories', ['url' => $category['url']], [
                'parent_id' => null,
                'name' => $category['name'],
                'image' => '',
                'size_chart' => '',
                'discount' => 0,
                'description' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'status' => 1,
                'menu_status' => 1,
            ]);
        }

        $categoryIdByUrl = $this->pluckIdsByKey('categories', 'url', array_column($this->categorySeedData(), 'url'));

        foreach ($this->categorySeedData() as $category) {
            $parentId = null;
            if ($category['parent_url'] !== null) {
                $parentId = $categoryIdByUrl[$category['parent_url']] ?? null;
            }

            DB::table('categories')
                ->where('url', $category['url'])
                ->update([
                    'parent_id' => $parentId,
                    'updated_at' => self::SNAPSHOT_AT,
                ]);
        }

        return $categoryIdByUrl;
    }

    private function seedBrands(): array
    {
        foreach ($this->brandSeedData() as $brand) {
            $this->persist('brands', ['url' => $brand['url']], [
                'name' => $brand['name'],
                'image' => '',
                'logo' => '',
                'discount' => 0,
                'description' => '',
                'meta_title' => '',
                'meta_description' => '',
                'meta_keywords' => '',
                'status' => 1,
            ]);
        }

        return $this->pluckIdsByKey('brands', 'url', array_column($this->brandSeedData(), 'url'));
    }

    private function seedProducts(array $categoryIdByUrl, array $brandIdByUrl, array $adminIdByEmail): array
    {
        $adminId = $adminIdByEmail['admin@admin.com'] ?? null;
        if ($adminId === null) {
            throw new \RuntimeException('Unable to resolve the primary seeded admin.');
        }

        foreach ($this->catalogSeedData() as $product) {
            $categoryId = $categoryIdByUrl[$product['category_url']] ?? null;
            $brandId = $brandIdByUrl[$product['brand_url']] ?? null;

            if ($categoryId === null || $brandId === null) {
                throw new \RuntimeException('Unable to resolve category or brand for product ' . $product['code']);
            }

            $colors = array_column($product['colors'], 'name');
            $sizeList = array_column($product['attributes'], 'size');
            $productStock = array_sum(array_column($product['attributes'], 'stock'));

            $this->persist('products', ['product_code' => $product['code']], [
                'category_id' => $categoryId,
                'brand_id' => $brandId,
                'admin_id' => $adminId,
                'admin_type' => 'admin',
                'product_name' => $product['name'],
                'product_url' => $product['product_url'],
                'product_color' => implode(', ', $colors),
                'group_code' => $product['code'],
                'product_price' => $product['product_price'],
                'product_discount' => $product['product_discount'],
                'product_discount_amount' => $product['product_discount_amount'],
                'discount_applied_on' => $product['discount_applied_on'],
                'product_gst' => 16,
                'final_price' => $product['final_price'],
                'material' => 'Premium synthetic leather',
                'bag_type' => $product['bag_type'],
                'closure_type' => 'Zip closure',
                'strap_type' => $product['strap_type'],
                'gender' => $product['gender'],
                'occasion' => $product['occasion'],
                'size' => implode(', ', $sizeList),
                'dimensions' => $product['dimensions'],
                'compartments' => $product['compartments'],
                'stock' => $productStock,
                'color_stock' => json_encode(array_fill_keys($colors, 9), JSON_UNESCAPED_SLASHES),
                'availability' => 'in_stock',
                'sort' => $product['sort'],
                'main_image' => $product['colors'][0]['image'],
                'product_video' => null,
                'description' => $product['description'],
                'search_keywords' => $product['search_keywords'],
                'meta_title' => $product['meta_title'],
                'meta_description' => $product['meta_description'],
                'meta_keywords' => $product['meta_keywords'],
                'is_featured' => 'Yes',
                'status' => 1,
            ]);
        }

        return $this->pluckIdsByKey('products', 'product_code', array_column($this->catalogSeedData(), 'code'));
    }

    private function seedProductAttributes(array $productIdByCode): void
    {
        foreach ($this->catalogSeedData() as $product) {
            $productId = $productIdByCode[$product['code']] ?? null;
            if ($productId === null) {
                throw new \RuntimeException('Unable to resolve product for attribute seed ' . $product['code']);
            }

            foreach ($product['attributes'] as $attribute) {
                $this->persist('products_attributes', ['sku' => $attribute['sku']], [
                    'product_id' => $productId,
                    'size' => $attribute['size'],
                    'price' => $attribute['price'],
                    'stock' => $attribute['stock'],
                    'sort' => $attribute['sort'],
                    'status' => 1,
                ]);
            }
        }
    }

    private function seedProductVariants(array $productIdByCode): void
    {
        foreach ($this->catalogSeedData() as $product) {
            $productId = $productIdByCode[$product['code']] ?? null;
            if ($productId === null) {
                throw new \RuntimeException('Unable to resolve product for variant seed ' . $product['code']);
            }

            foreach ($product['colors'] as $color) {
                foreach ($product['attributes'] as $attribute) {
                    $this->persist('product_variants', [
                        'product_id' => $productId,
                        'size' => $attribute['size'],
                        'color' => $color['name'],
                    ], [
                        'stock' => $attribute['variant_stock'],
                    ]);
                }
            }
        }
    }

    private function seedProductImages(array $productIdByCode): void
    {
        foreach ($this->catalogSeedData() as $product) {
            $productId = $productIdByCode[$product['code']] ?? null;
            if ($productId === null) {
                throw new \RuntimeException('Unable to resolve product for image seed ' . $product['code']);
            }

            foreach ($product['colors'] as $index => $color) {
                $this->persist('products_images', [
                    'product_id' => $productId,
                    'color' => $color['name'],
                    'sort' => $index + 1,
                ], [
                    'image' => $color['image'],
                    'status' => 1,
                ]);
            }
        }
    }

    private function seedBanners(): void
    {
        foreach ($this->bannerSeedData() as $banner) {
            $this->persist('banners', ['title' => $banner['title']], [
                'image' => $banner['image'],
                'type' => $banner['type'],
                'link' => $banner['link'],
                'alt' => $banner['alt'],
                'sort' => $banner['sort'],
                'status' => 1,
            ]);
        }
    }

    private function seedCoupons(array $categoryIdByUrl, array $brandIdByUrl): void
    {
        foreach ($this->couponSeedData() as $coupon) {
            $categoryIds = [];
            foreach ($coupon['category_urls'] as $url) {
                if (!isset($categoryIdByUrl[$url])) {
                    throw new \RuntimeException('Unable to resolve coupon category ' . $url);
                }
                $categoryIds[] = $categoryIdByUrl[$url];
            }

            $brandIds = [];
            foreach ($coupon['brand_urls'] as $url) {
                if (!isset($brandIdByUrl[$url])) {
                    throw new \RuntimeException('Unable to resolve coupon brand ' . $url);
                }
                $brandIds[] = $brandIdByUrl[$url];
            }

            $this->persist('coupons', ['coupon_code' => $coupon['coupon_code']], [
                'coupon_option' => 'Manual',
                'categories' => $categoryIds === [] ? null : json_encode($categoryIds, JSON_UNESCAPED_SLASHES),
                'brands' => $brandIds === [] ? null : json_encode($brandIds, JSON_UNESCAPED_SLASHES),
                'users' => $coupon['users'] === [] ? null : json_encode($coupon['users'], JSON_UNESCAPED_SLASHES),
                'coupon_type' => $coupon['coupon_type'],
                'amount_type' => $coupon['amount_type'],
                'amount' => $coupon['amount'],
                'min_qty' => $coupon['min_qty'],
                'max_qty' => $coupon['max_qty'],
                'min_cart_value' => $coupon['min_cart_value'],
                'max_cart_value' => $coupon['max_cart_value'],
                'usage_limit_per_user' => $coupon['usage_limit_per_user'],
                'total_usage_limit' => $coupon['total_usage_limit'],
                'max_discount' => null,
                'used_count' => 0,
                'expiry_date' => $coupon['expiry_date'],
                'status' => 1,
                'visible' => $coupon['visible'],
            ]);
        }
    }

    private function seedReviews(array $productIdByCode, array $userIdByEmail): void
    {
        foreach ($this->reviewSeedData() as $review) {
            $productId = $productIdByCode[$review['product_code']] ?? null;
            $userId = $userIdByEmail[$review['user_email']] ?? null;

            if ($productId === null || $userId === null) {
                throw new \RuntimeException('Unable to resolve product or user for seeded review.');
            }

            $this->persist('reviews', [
                'product_id' => $productId,
                'user_id' => $userId,
            ], [
                'rating' => $review['rating'],
                'review' => $review['review'],
                'status' => $review['status'],
            ]);
        }
    }

    private function persist(string $table, array $match, array $values): void
    {
        $query = DB::table($table)->where($match);

        if ($query->exists()) {
            $query->update(array_merge($values, [
                'updated_at' => self::SNAPSHOT_AT,
            ]));

            return;
        }

        DB::table($table)->insert(array_merge($match, $values, [
            'created_at' => self::SNAPSHOT_AT,
            'updated_at' => self::SNAPSHOT_AT,
        ]));
    }

    private function pluckIdsByKey(string $table, string $keyColumn, array $keys): array
    {
        return DB::table($table)
            ->whereIn($keyColumn, $keys)
            ->pluck('id', $keyColumn)
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function adminSeedData(): array
    {
        return [
            [
                'name' => 'Sheryl Awinja',
                'role' => 'admin',
                'mobile' => '1234567890',
                'email' => 'admin@admin.com',
                'password' => '12345678',
                'status' => 1,
            ],
            [
                'name' => 'Shera',
                'role' => 'subadmin',
                'mobile' => '0740549910',
                'email' => 'shera@admin.com',
                'password' => '12345678',
                'status' => 1,
            ],
            [
                'name' => 'Jane Doe',
                'role' => 'subadmin',
                'mobile' => '0123456789',
                'email' => 'jane@admin.com',
                'password' => '12345678',
                'status' => 1,
            ],
        ];
    }

    private function userSeedData(): array
    {
        return [
            [
                'name' => 'Sherly Awinja',
                'email' => 'awinjasherly@gmail.com',
                'password' => 'password',
                'user_type' => 'Customer',
                'status' => 1,
                'country' => 'Kenya',
                'county' => null,
                'sub_county' => null,
                'phone' => null,
                'is_admin' => 0,
            ],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password',
                'user_type' => 'Customer',
                'status' => 1,
                'country' => 'Kenya',
                'county' => 'Nairobi',
                'sub_county' => 'Westlands',
                'phone' => '0710000001',
                'is_admin' => 0,
            ],
            [
                'name' => 'Review One',
                'email' => 'reviewer.one@example.com',
                'password' => 'password',
                'user_type' => 'Customer',
                'status' => 1,
                'country' => 'Kenya',
                'county' => 'Nairobi',
                'sub_county' => 'Kasarani',
                'phone' => '0710000002',
                'is_admin' => 0,
            ],
            [
                'name' => 'Review Two',
                'email' => 'reviewer.two@example.com',
                'password' => 'password',
                'user_type' => 'Customer',
                'status' => 1,
                'country' => 'Kenya',
                'county' => 'Mombasa',
                'sub_county' => 'Nyali',
                'phone' => '0710000003',
                'is_admin' => 0,
            ],
            [
                'name' => 'Review Three',
                'email' => 'reviewer.three@example.com',
                'password' => 'password',
                'user_type' => 'Customer',
                'status' => 1,
                'country' => 'Kenya',
                'county' => 'Kiambu',
                'sub_county' => 'Ruiru',
                'phone' => '0710000004',
                'is_admin' => 0,
            ],
        ];
    }

    private function categorySeedData(): array
    {
        return [
            ['parent_url' => null, 'name' => 'Handbags', 'url' => 'handbags'],
            ['parent_url' => null, 'name' => 'Travel Bags', 'url' => 'travel-bags'],
            ['parent_url' => null, 'name' => 'Gym Bags', 'url' => 'gym-bags'],
            ['parent_url' => null, 'name' => 'Organizers', 'url' => 'organizers'],
            ['parent_url' => 'handbags', 'name' => 'Tote Bags', 'url' => 'tote-bags'],
            ['parent_url' => 'handbags', 'name' => 'Shoulder Bags', 'url' => 'shoulder-bags'],
            ['parent_url' => 'travel-bags', 'name' => 'Duffle Bags', 'url' => 'duffle-bags'],
            ['parent_url' => 'travel-bags', 'name' => 'Carry-on Bags', 'url' => 'carry-on-bags'],
            ['parent_url' => 'gym-bags', 'name' => 'Yoga Bags', 'url' => 'yoga-bags'],
            ['parent_url' => 'gym-bags', 'name' => 'Gym Duffel', 'url' => 'gym-duffel'],
            ['parent_url' => 'organizers', 'name' => 'Makeup Bags', 'url' => 'makeup-bags'],
            ['parent_url' => 'organizers', 'name' => 'Lunch Bags', 'url' => 'lunch-bags'],
            ['parent_url' => 'tote-bags', 'name' => 'Classic Tote Bags', 'url' => 'classic-tote-bags'],
            ['parent_url' => 'shoulder-bags', 'name' => 'Hobo Bags', 'url' => 'hobo-bags'],
            ['parent_url' => 'yoga-bags', 'name' => 'Mat Holders', 'url' => 'mat-holders'],
        ];
    }

    private function brandSeedData(): array
    {
        return [
            ['name' => 'Arrow', 'url' => 'arrow'],
            ['name' => 'Gap', 'url' => 'gap'],
            ['name' => 'Lee', 'url' => 'lee'],
            ['name' => 'Monte Carlo', 'url' => 'monte-carlo'],
            ['name' => 'Peter England', 'url' => 'peter-england'],
        ];
    }

    private function catalogSeedData(): array
    {
        return array_merge(
            $this->catalogPartOne(),
            $this->catalogPartTwo()
        );
    }

    private function bannerSeedData(): array
    {
        return [
            [
                'image' => '1770200582_pexels-codioful-7130497.jpg',
                'type' => 'Slider',
                'link' => '/handbags',
                'title' => 'Everyday Handbags',
                'alt' => 'Elevated silhouettes for work and weekends',
                'sort' => 30,
            ],
            [
                'image' => '1771519992_Untitled design (2).jpg',
                'type' => 'Slider',
                'link' => '/travel-bags',
                'title' => 'Travel Ready',
                'alt' => 'Structured duffles and carry-on companions',
                'sort' => 20,
            ],
            [
                'image' => '1774111720_1770200582_pexels-codioful-7130497.jpg',
                'type' => 'Slider',
                'link' => '/organizers',
                'title' => 'Organized Essentials',
                'alt' => 'Compact cases for beauty, lunch, and daily carry',
                'sort' => 10,
            ],
            [
                'image' => '1774007367_1771519992_Untitled design (2).jpg',
                'type' => 'Fix',
                'link' => '/gym-bags',
                'title' => 'Studio to Street',
                'alt' => 'Gym duffels and yoga bags with clean utility',
                'sort' => 20,
            ],
            [
                'image' => '1770200533_pexels-codioful-7130497.jpg',
                'type' => 'Fix',
                'link' => '/handbags',
                'title' => 'New Season Edit',
                'alt' => 'Refined handbags with polished finishes',
                'sort' => 10,
            ],
        ];
    }

    private function couponSeedData(): array
    {
        return [
            [
                'coupon_code' => 'WELCOME10',
                'category_urls' => [],
                'brand_urls' => [],
                'users' => [],
                'coupon_type' => 'Multiple',
                'amount_type' => 'Percentage',
                'amount' => 10.00,
                'min_qty' => null,
                'max_qty' => null,
                'min_cart_value' => 0.00,
                'max_cart_value' => 10000.00,
                'usage_limit_per_user' => 0,
                'total_usage_limit' => 0,
                'expiry_date' => '2026-06-16',
                'visible' => 1,
            ],
            [
                'coupon_code' => 'TRAVEL500',
                'category_urls' => ['travel-bags', 'duffle-bags', 'carry-on-bags'],
                'brand_urls' => ['lee', 'monte-carlo'],
                'users' => ['test@example.com'],
                'coupon_type' => 'Single',
                'amount_type' => 'Fixed',
                'amount' => 500.00,
                'min_qty' => 1,
                'max_qty' => 10,
                'min_cart_value' => 3000.00,
                'max_cart_value' => 20000.00,
                'usage_limit_per_user' => 1,
                'total_usage_limit' => 100,
                'expiry_date' => '2026-07-16',
                'visible' => 0,
            ],
        ];
    }

    private function reviewSeedData(): array
    {
        return [
            [
                'product_code' => 'TOTE-001',
                'user_email' => 'test@example.com',
                'rating' => 5,
                'review' => 'Clean finish, strong stitching, and enough room for a full work day.',
                'status' => 1,
            ],
            [
                'product_code' => 'TRV-003',
                'user_email' => 'reviewer.one@example.com',
                'rating' => 4,
                'review' => 'Great weekender size and it keeps its shape well on short trips.',
                'status' => 1,
            ],
            [
                'product_code' => 'GYM-006',
                'user_email' => 'reviewer.two@example.com',
                'rating' => 4,
                'review' => 'Useful compartments and the shoe section is practical for gym runs.',
                'status' => 1,
            ],
            [
                'product_code' => 'ORG-007',
                'user_email' => 'reviewer.three@example.com',
                'rating' => 3,
                'review' => 'Good organizer layout. I would still like one extra inner pocket.',
                'status' => 0,
            ],
        ];
    }

    private function catalogPartOne(): array
    {
        return [
            [
                'code' => 'TOTE-001',
                'name' => 'Structured Office Tote',
                'product_url' => 'structured-office-tote',
                'category_url' => 'classic-tote-bags',
                'brand_url' => 'arrow',
                'product_price' => 6800,
                'product_discount' => 10,
                'product_discount_amount' => 680,
                'discount_applied_on' => 'product',
                'final_price' => 6120,
                'bag_type' => 'Tote',
                'strap_type' => 'Leather',
                'gender' => 'women',
                'occasion' => 'work',
                'dimensions' => '42x30x12 cm',
                'compartments' => 3,
                'sort' => 1,
                'description' => '<p>A polished tote with enough room for daily essentials, laptops, and quick errands.</p>',
                'search_keywords' => 'office tote handbag structured leather women work',
                'meta_title' => 'Structured Office Tote',
                'meta_description' => 'A polished tote with enough room for daily essentials, laptops, and quick errands.',
                'meta_keywords' => 'office tote handbag structured leather women work',
                'colors' => [
                    ['name' => 'Tan', 'image' => '1765963495_3505.jpg'],
                    ['name' => 'Black', 'image' => '1770291491_3968.jpg'],
                ],
                'attributes' => [
                    ['size' => 'Small', 'sku' => 'TOTE-001-S', 'price' => '6500.00', 'stock' => 4, 'sort' => 1, 'variant_stock' => 2],
                    ['size' => 'Medium', 'sku' => 'TOTE-001-M', 'price' => '6800.00', 'stock' => 6, 'sort' => 2, 'variant_stock' => 3],
                    ['size' => 'Large', 'sku' => 'TOTE-001-L', 'price' => '7200.00', 'stock' => 8, 'sort' => 3, 'variant_stock' => 4],
                ],
            ],
            [
                'code' => 'SHD-002',
                'name' => 'City Hobo Shoulder Bag',
                'product_url' => 'city-hobo-shoulder-bag',
                'category_url' => 'hobo-bags',
                'brand_url' => 'gap',
                'product_price' => 6200,
                'product_discount' => 5,
                'product_discount_amount' => 310,
                'discount_applied_on' => 'product',
                'final_price' => 5890,
                'bag_type' => 'Shoulder',
                'strap_type' => 'Leather',
                'gender' => 'women',
                'occasion' => 'casual',
                'dimensions' => '36x28x10 cm',
                'compartments' => 2,
                'sort' => 2,
                'description' => '<p>A relaxed hobo silhouette designed for all-day carry with understated edge.</p>',
                'search_keywords' => 'hobo shoulder bag city bag women casual',
                'meta_title' => 'City Hobo Shoulder Bag',
                'meta_description' => 'A relaxed hobo silhouette designed for all-day carry with understated edge.',
                'meta_keywords' => 'hobo shoulder bag city bag women casual',
                'colors' => [
                    ['name' => 'Brown', 'image' => '1770291627_3729.jpg'],
                    ['name' => 'Burgundy', 'image' => '1772745280_9174.jpg'],
                ],
                'attributes' => [
                    ['size' => 'Small', 'sku' => 'SHD-002-S', 'price' => '5900.00', 'stock' => 4, 'sort' => 1, 'variant_stock' => 2],
                    ['size' => 'Medium', 'sku' => 'SHD-002-M', 'price' => '6200.00', 'stock' => 6, 'sort' => 2, 'variant_stock' => 3],
                    ['size' => 'Large', 'sku' => 'SHD-002-L', 'price' => '6600.00', 'stock' => 8, 'sort' => 3, 'variant_stock' => 4],
                ],
            ],
            [
                'code' => 'TRV-003',
                'name' => 'Urban Weekender Duffle',
                'product_url' => 'urban-weekender-duffle',
                'category_url' => 'duffle-bags',
                'brand_url' => 'lee',
                'product_price' => 7400,
                'product_discount' => 12,
                'product_discount_amount' => 888,
                'discount_applied_on' => 'product',
                'final_price' => 6512,
                'bag_type' => 'Duffel',
                'strap_type' => 'Fabric',
                'gender' => 'men',
                'occasion' => 'travel',
                'dimensions' => '48x28x24 cm',
                'compartments' => 4,
                'sort' => 3,
                'description' => '<p>A travel duffle built for short trips, gym runs, and clean overhead-bin packing.</p>',
                'search_keywords' => 'weekender duffle travel bag men olive black',
                'meta_title' => 'Urban Weekender Duffle',
                'meta_description' => 'A travel duffle built for short trips, gym runs, and clean overhead-bin packing.',
                'meta_keywords' => 'weekender duffle travel bag men olive black',
                'colors' => [
                    ['name' => 'Olive', 'image' => '1772745620_6091.jpg'],
                    ['name' => 'Black', 'image' => '1772745621_2634.jpg'],
                ],
                'attributes' => [
                    ['size' => 'Small', 'sku' => 'TRV-003-S', 'price' => '7100.00', 'stock' => 4, 'sort' => 1, 'variant_stock' => 2],
                    ['size' => 'Medium', 'sku' => 'TRV-003-M', 'price' => '7400.00', 'stock' => 6, 'sort' => 2, 'variant_stock' => 3],
                    ['size' => 'Large', 'sku' => 'TRV-003-L', 'price' => '7800.00', 'stock' => 8, 'sort' => 3, 'variant_stock' => 4],
                ],
            ],
            [
                'code' => 'TRV-004',
                'name' => 'Cabin Carry-On Holdall',
                'product_url' => 'cabin-carry-on-holdall',
                'category_url' => 'carry-on-bags',
                'brand_url' => 'monte-carlo',
                'product_price' => 7900,
                'product_discount' => 8,
                'product_discount_amount' => 632,
                'discount_applied_on' => 'product',
                'final_price' => 7268,
                'bag_type' => 'Weekender',
                'strap_type' => 'Adjustable strap',
                'gender' => 'unisex',
                'occasion' => 'travel',
                'dimensions' => '50x32x22 cm',
                'compartments' => 4,
                'sort' => 4,
                'description' => '<p>A compact holdall shaped for cabin travel with smooth zip access and stable carry.</p>',
                'search_keywords' => 'carry on holdall travel bag cabin navy grey',
                'meta_title' => 'Cabin Carry-On Holdall',
                'meta_description' => 'A compact holdall shaped for cabin travel with smooth zip access and stable carry.',
                'meta_keywords' => 'carry on holdall travel bag cabin navy grey',
                'colors' => [
                    ['name' => 'Navy', 'image' => '1772745621_3092.jpg'],
                    ['name' => 'Grey', 'image' => '1772745713_4571.jpg'],
                ],
                'attributes' => [
                    ['size' => 'Small', 'sku' => 'TRV-004-S', 'price' => '7600.00', 'stock' => 4, 'sort' => 1, 'variant_stock' => 2],
                    ['size' => 'Medium', 'sku' => 'TRV-004-M', 'price' => '7900.00', 'stock' => 6, 'sort' => 2, 'variant_stock' => 3],
                    ['size' => 'Large', 'sku' => 'TRV-004-L', 'price' => '8300.00', 'stock' => 8, 'sort' => 3, 'variant_stock' => 4],
                ],
            ],
        ];
    }

    private function catalogPartTwo(): array
    {
        return [
            [
                'code' => 'GYM-005',
                'name' => 'Performance Yoga Carrier',
                'product_url' => 'performance-yoga-carrier',
                'category_url' => 'mat-holders',
                'brand_url' => 'peter-england',
                'product_price' => 5600,
                'product_discount' => 0,
                'product_discount_amount' => 0,
                'discount_applied_on' => 'brand',
                'final_price' => 5600,
                'bag_type' => 'Yoga',
                'strap_type' => 'Adjustable strap',
                'gender' => 'women',
                'occasion' => 'gym',
                'dimensions' => '72x18x18 cm',
                'compartments' => 2,
                'sort' => 5,
                'description' => '<p>A yoga bag that keeps mats, bottles, and quick-change items streamlined.</p>',
                'search_keywords' => 'yoga bag mat holder gym teal black',
                'meta_title' => 'Performance Yoga Carrier',
                'meta_description' => 'A yoga bag that keeps mats, bottles, and quick-change items streamlined.',
                'meta_keywords' => 'yoga bag mat holder gym teal black',
                'colors' => [
                    ['name' => 'Teal', 'image' => '1772746073_4787.jpg'],
                    ['name' => 'Black', 'image' => '1772746302_8428.jpg'],
                ],
                'attributes' => [
                    ['size' => 'Small', 'sku' => 'GYM-005-S', 'price' => '5300.00', 'stock' => 4, 'sort' => 1, 'variant_stock' => 2],
                    ['size' => 'Medium', 'sku' => 'GYM-005-M', 'price' => '5600.00', 'stock' => 6, 'sort' => 2, 'variant_stock' => 3],
                    ['size' => 'Large', 'sku' => 'GYM-005-L', 'price' => '6000.00', 'stock' => 8, 'sort' => 3, 'variant_stock' => 4],
                ],
            ],
            [
                'code' => 'GYM-006',
                'name' => 'Gym Duffel Pro',
                'product_url' => 'gym-duffel-pro',
                'category_url' => 'gym-duffel',
                'brand_url' => 'arrow',
                'product_price' => 6900,
                'product_discount' => 15,
                'product_discount_amount' => 1035,
                'discount_applied_on' => 'product',
                'final_price' => 5865,
                'bag_type' => 'Duffel',
                'strap_type' => 'Webbing',
                'gender' => 'unisex',
                'occasion' => 'gym',
                'dimensions' => '46x27x25 cm',
                'compartments' => 5,
                'sort' => 6,
                'description' => '<p>A high-capacity gym duffel with separate zones for shoes, apparel, and accessories.</p>',
                'search_keywords' => 'gym duffel training bag charcoal blue',
                'meta_title' => 'Gym Duffel Pro',
                'meta_description' => 'A high-capacity gym duffel with separate zones for shoes, apparel, and accessories.',
                'meta_keywords' => 'gym duffel training bag charcoal blue',
                'colors' => [
                    ['name' => 'Charcoal', 'image' => '1772747297_9027.jpg'],
                    ['name' => 'Blue', 'image' => '1772747790_3125.jpg'],
                ],
                'attributes' => [
                    ['size' => 'Small', 'sku' => 'GYM-006-S', 'price' => '6600.00', 'stock' => 4, 'sort' => 1, 'variant_stock' => 2],
                    ['size' => 'Medium', 'sku' => 'GYM-006-M', 'price' => '6900.00', 'stock' => 6, 'sort' => 2, 'variant_stock' => 3],
                    ['size' => 'Large', 'sku' => 'GYM-006-L', 'price' => '7300.00', 'stock' => 8, 'sort' => 3, 'variant_stock' => 4],
                ],
            ],
            [
                'code' => 'ORG-007',
                'name' => 'Cosmetic Organizer Case',
                'product_url' => 'cosmetic-organizer-case',
                'category_url' => 'makeup-bags',
                'brand_url' => 'gap',
                'product_price' => 3900,
                'product_discount' => 0,
                'product_discount_amount' => 0,
                'discount_applied_on' => 'brand',
                'final_price' => 3900,
                'bag_type' => 'Cosmetic',
                'strap_type' => 'Top handle',
                'gender' => 'women',
                'occasion' => 'casual',
                'dimensions' => '24x16x12 cm',
                'compartments' => 3,
                'sort' => 7,
                'description' => '<p>A compact case for beauty routines, touch-up kits, and travel-ready organization.</p>',
                'search_keywords' => 'cosmetic organizer makeup bag pink beige',
                'meta_title' => 'Cosmetic Organizer Case',
                'meta_description' => 'A compact case for beauty routines, touch-up kits, and travel-ready organization.',
                'meta_keywords' => 'cosmetic organizer makeup bag pink beige',
                'colors' => [
                    ['name' => 'Pink', 'image' => '1772747797_6655.jpg'],
                    ['name' => 'Beige', 'image' => '1772747981_8622.jpg'],
                ],
                'attributes' => [
                    ['size' => 'Small', 'sku' => 'ORG-007-S', 'price' => '3600.00', 'stock' => 4, 'sort' => 1, 'variant_stock' => 2],
                    ['size' => 'Medium', 'sku' => 'ORG-007-M', 'price' => '3900.00', 'stock' => 6, 'sort' => 2, 'variant_stock' => 3],
                    ['size' => 'Large', 'sku' => 'ORG-007-L', 'price' => '4300.00', 'stock' => 8, 'sort' => 3, 'variant_stock' => 4],
                ],
            ],
            [
                'code' => 'ORG-008',
                'name' => 'Insulated Lunch Tote',
                'product_url' => 'insulated-lunch-tote',
                'category_url' => 'lunch-bags',
                'brand_url' => 'lee',
                'product_price' => 3400,
                'product_discount' => 0,
                'product_discount_amount' => 0,
                'discount_applied_on' => 'brand',
                'final_price' => 3400,
                'bag_type' => 'Organizer',
                'strap_type' => 'Fabric',
                'gender' => 'unisex',
                'occasion' => 'casual',
                'dimensions' => '26x22x14 cm',
                'compartments' => 2,
                'sort' => 8,
                'description' => '<p>An insulated lunch tote with compact proportions and an easy-clean interior.</p>',
                'search_keywords' => 'insulated lunch tote organizer mustard cream',
                'meta_title' => 'Insulated Lunch Tote',
                'meta_description' => 'An insulated lunch tote with compact proportions and an easy-clean interior.',
                'meta_keywords' => 'insulated lunch tote organizer mustard cream',
                'colors' => [
                    ['name' => 'Mustard', 'image' => '1772794907_7129.jpg'],
                    ['name' => 'Cream', 'image' => '1772794917_6948.jpg'],
                ],
                'attributes' => [
                    ['size' => 'Small', 'sku' => 'ORG-008-S', 'price' => '3100.00', 'stock' => 4, 'sort' => 1, 'variant_stock' => 2],
                    ['size' => 'Medium', 'sku' => 'ORG-008-M', 'price' => '3400.00', 'stock' => 6, 'sort' => 2, 'variant_stock' => 3],
                    ['size' => 'Large', 'sku' => 'ORG-008-L', 'price' => '3800.00', 'stock' => 8, 'sort' => 3, 'variant_stock' => 4],
                ],
            ],
        ];
    }
}
