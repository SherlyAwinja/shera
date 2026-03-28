<?php

namespace Tests\Feature;

use App\Services\Front\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductDetailSimilarProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_related_products_only_include_available_items_and_expose_safe_quick_add_metadata(): void
    {
        $parentCategoryId = $this->createCategory([
            'name' => 'Bags',
            'url' => 'bags',
            'parent_id' => null,
        ]);

        $childCategoryId = $this->createCategory([
            'name' => 'Totes',
            'url' => 'totes',
            'parent_id' => $parentCategoryId,
        ]);

        $currentProductId = $this->createProduct([
            'category_id' => $childCategoryId,
            'product_name' => 'Current Product',
            'product_url' => 'current-product',
            'product_code' => 'CURRENT-001',
            'stock' => 5,
        ]);

        $simpleId = $this->createProduct([
            'category_id' => $childCategoryId,
            'product_name' => 'Simple Similar',
            'product_url' => 'simple-similar',
            'product_code' => 'SIM-001',
            'stock' => 7,
        ]);

        $singleSizeId = $this->createProduct([
            'category_id' => $childCategoryId,
            'product_name' => 'Single Size Similar',
            'product_url' => 'single-size-similar',
            'product_code' => 'SIM-002',
            'stock' => 0,
        ]);

        $multiSizeId = $this->createProduct([
            'category_id' => $childCategoryId,
            'product_name' => 'Multi Size Similar',
            'product_url' => 'multi-size-similar',
            'product_code' => 'SIM-003',
            'stock' => 0,
        ]);

        $parentCategoryProductId = $this->createProduct([
            'category_id' => $parentCategoryId,
            'product_name' => 'Parent Category Similar',
            'product_url' => 'parent-category-similar',
            'product_code' => 'SIM-004',
            'stock' => 3,
        ]);

        $this->createProduct([
            'category_id' => $childCategoryId,
            'product_name' => 'Out of Stock Similar',
            'product_url' => 'out-of-stock-similar',
            'product_code' => 'SIM-005',
            'stock' => 0,
        ]);

        $otherCategoryId = $this->createCategory([
            'name' => 'Travel',
            'url' => 'travel',
            'parent_id' => null,
        ]);

        $this->createProduct([
            'category_id' => $otherCategoryId,
            'product_name' => 'Different Category Similar',
            'product_url' => 'different-category-similar',
            'product_code' => 'SIM-006',
            'stock' => 6,
        ]);

        $this->createAttribute($singleSizeId, 'M', 4, 'SIM-002-M');
        $this->createAttribute($multiSizeId, 'S', 3, 'SIM-003-S');
        $this->createAttribute($multiSizeId, 'L', 2, 'SIM-003-L');

        $product = app(ProductService::class)->getProductDetailByUrl('current-product');

        $this->assertNotNull($product);

        $similarProducts = $product->similar_products->keyBy('product_url');

        $this->assertArrayHasKey('simple-similar', $similarProducts->all());
        $this->assertArrayHasKey('single-size-similar', $similarProducts->all());
        $this->assertArrayHasKey('multi-size-similar', $similarProducts->all());
        $this->assertArrayHasKey('parent-category-similar', $similarProducts->all());
        $this->assertArrayNotHasKey('out-of-stock-similar', $similarProducts->all());
        $this->assertArrayNotHasKey('different-category-similar', $similarProducts->all());

        $simple = $similarProducts->get('simple-similar');
        $singleSize = $similarProducts->get('single-size-similar');
        $multiSize = $similarProducts->get('multi-size-similar');

        $this->assertTrue($simple->can_quick_add);
        $this->assertSame('NA', $simple->quick_add_size);
        $this->assertFalse($simple->has_selectable_sizes);

        $this->assertTrue($singleSize->can_quick_add);
        $this->assertSame('M', $singleSize->quick_add_size);
        $this->assertTrue($singleSize->has_selectable_sizes);
        $this->assertTrue($singleSize->is_available);

        $this->assertFalse($multiSize->can_quick_add);
        $this->assertNull($multiSize->quick_add_size);
        $this->assertTrue($multiSize->has_selectable_sizes);
        $this->assertTrue($multiSize->is_available);
    }

    private function createCategory(array $overrides = []): int
    {
        static $sort = 1;

        return (int) DB::table('categories')->insertGetId(array_merge([
            'parent_id' => null,
            'name' => 'Category ' . $sort,
            'image' => null,
            'size_chart' => null,
            'discount' => 0,
            'description' => null,
            'url' => 'category-' . $sort,
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'menu_status' => 1,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function createProduct(array $overrides = []): int
    {
        static $sort = 1;

        return (int) DB::table('products')->insertGetId(array_merge([
            'category_id' => 1,
            'brand_id' => null,
            'admin_id' => 1,
            'admin_type' => 'admin',
            'product_name' => 'Product ' . $sort,
            'product_url' => 'product-' . $sort,
            'product_code' => 'PRODUCT-' . str_pad((string) $sort, 3, '0', STR_PAD_LEFT),
            'product_color' => 'Black',
            'group_code' => null,
            'product_price' => 100,
            'product_discount' => 0,
            'product_discount_amount' => 0,
            'discount_applied_on' => 'none',
            'product_gst' => 0,
            'final_price' => 100,
            'material' => null,
            'bag_type' => null,
            'closure_type' => null,
            'strap_type' => null,
            'size' => null,
            'dimensions' => null,
            'compartments' => 0,
            'stock' => 10,
            'sort' => 0,
            'main_image' => null,
            'product_video' => null,
            'description' => null,
            'search_keywords' => null,
            'meta_title' => null,
            'meta_description' => null,
            'meta_keywords' => null,
            'is_featured' => 'No',
            'status' => 1,
            'created_at' => now()->addSeconds($sort),
            'updated_at' => now()->addSeconds($sort),
        ], $overrides));
    }

    private function createAttribute(int $productId, string $size, int $stock, string $sku): int
    {
        return (int) DB::table('products_attributes')->insertGetId([
            'product_id' => $productId,
            'size' => $size,
            'sku' => $sku,
            'price' => 120,
            'stock' => $stock,
            'sort' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
