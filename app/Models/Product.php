<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use Searchable;

    protected $casts = [
        'dimensions' => 'array',
        'color_stock' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo('App\Models\Category', 'category_id')->with('parentCategory');
    }

    public function brand()
    {
        return $this->belongsTo(\App\Models\Brand::class, 'brand_id');
    }

    public function getProductDimensionsAttribute()
    {
        return $this->dimensions;
    }

    public function product_images()
    {
        return $this->hasMany(ProductsImage::class)->orderBy('sort', 'asc');
    }

    public function attributes()
    {
        return $this->hasMany('App\Models\ProductsAttribute');
    }

    public function productVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('color')->orderBy('size');
    }

    public function filterValues()
    {
        return $this->belongsToMany(FilterValue::class, 'product_filter_values', 'product_id', 'filter_value_id');
    }

    // Backward-compatible alias.
    public function filtersValues()
    {
        return $this->filterValues();
    }

    public function toSearchableArray()
    {
        $categoryName = $this->category->name ?? null;
        return [
            'id' => $this->id,
            'product_name'=> $this->product_name,
            'category' => $categoryName,
            'search_keywords' => $this->search_keywords
        ];
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'products_categories', 'product_id', 'category_id');
    }

    public function otherCategories()
    {
        return $this->hasMany(ProductsCategory::class, 'product_id');
    }

    public static function getAttributePrice($product_id, $size, ?string $color = null)
    {
        $normalizedSize = trim((string) $size);
        $normalizedColor = trim((string) $color);
        $normalizedColor = $normalizedColor !== '' ? $normalizedColor : null;

        // Get attribute
        $attribute = ProductsAttribute::where([
            'product_id' => $product_id,
            'size' => $normalizedSize,
            'status' => 1
        ])->first();

        // Get product
        $product = self::with(['productVariants' => function ($query) use ($normalizedSize) {
                $query->where('size', $normalizedSize);
            }])
            ->select('id', 'category_id', 'brand_id', 'product_discount', 'product_price')
            ->where('id', $product_id)
            ->first();

        if (!$product) {
            return ['status' => false];
        }

        $sizeVariants = $product->productVariants;
        $matchingVariants = $normalizedColor !== null
            ? $sizeVariants->filter(function (ProductVariant $variant) use ($normalizedColor) {
                return strtolower(trim((string) $variant->color)) === strtolower($normalizedColor);
            })->values()
            : $sizeVariants;

        if ($normalizedColor !== null && $sizeVariants->isNotEmpty() && $matchingVariants->isEmpty()) {
            return ['status' => false];
        }

        if (!$attribute && $matchingVariants->isEmpty()) {
            return ['status' => false];
        }

        $basePrice = $attribute
            ? (float) $attribute->price
            : (float) ($product->product_price ?? 0);

        // Discounts
        $productDisc = (float) ($product->product_discount ?? 0);

        $categoryDisc = 0;
        if ($product->category_id) {
            $cat = Category::select('discount')->find($product->category_id);
            $categoryDisc = (float) ($cat->discount ?? 0);
        }

        $brandDisc = 0;
        if ($product->brand_id) {
            $brand = Brand::select('discount')->find($product->brand_id);
            $brandDisc = (float) ($brand->discount ?? 0);
        }

        // Apply highest priority discount
        $applied = 0;

        if ($productDisc > 0) {
            $applied = $productDisc;
        } elseif ($categoryDisc > 0) {
            $applied = $categoryDisc;
        } elseif ($brandDisc > 0) {
            $applied = $brandDisc;
        }

        // Final price calculation
        $final = $applied > 0
            ? round($basePrice - ($basePrice * $applied / 100))
            : round($basePrice);

        $discountAmt = $basePrice - $final;
        $hasVariantSelection = $matchingVariants->isNotEmpty();

        if ($hasVariantSelection) {
            $stock = (int) $matchingVariants->sum('stock');
            $inStock = $stock > 0;
            $descriptor = $normalizedColor !== null
                ? sprintf('size %s in %s', $normalizedSize, $normalizedColor)
                : sprintf('size %s', $normalizedSize);
            $stockMessage = $inStock
                ? sprintf('%d unit%s available in %s.', $stock, $stock === 1 ? '' : 's', $descriptor)
                : sprintf('%s is currently out of stock.', ucfirst($descriptor));
        } else {
            $stock = (int) ($attribute->stock ?? 0);
            $inStock = $stock > 0;
            $stockMessage = $inStock
                ? sprintf('%d unit%s available in size %s.', $stock, $stock === 1 ? '' : 's', $attribute->size)
                : sprintf('Size %s is currently out of stock.', $attribute->size);
        }

        return [
            'status' => true,
            'product_price' => (int) $basePrice,
            'final_price' => (int) $final,
            'discount' => (int) $discountAmt,
            'percent' => (int) $applied,
            'stock' => $stock,
            'in_stock' => $inStock,
            'stock_label' => $inStock ? 'In stock' : 'Out of stock',
            'stock_message' => $stockMessage,
        ];
    }

    public static function productStatus($product_id)
    {
        return self::where('id', $product_id)->value('status');
    }

    public function reviews()
    {
        return $this->hasMany(\App\Models\Review::class, 'product_id');
    }

    public function averageRating()
    {
        return (float) $this->reviews()->where('status', 1)->avg('rating')??0;
    }
}
