<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;

class ProductsFilter extends Model
{
    public static function getAvailability($catIds)
    {
        if (empty($catIds)) {
            return [];
        }

        if (Schema::hasColumn('products', 'availability')) {
            $availabilityValues = Product::select('availability')
                ->whereIn('category_id', $catIds)
                ->whereNotNull('availability')
                ->where('availability', '!=', '')
                ->groupBy('availability')
                ->pluck('availability')
                ->toArray();

            if (!empty($availabilityValues)) {
                return $availabilityValues;
            }
        }

        $availability = [];
        $productsQuery = Product::whereIn('category_id', $catIds);

        if ((clone $productsQuery)->where('stock', '>', 0)->exists()) {
            $availability[] = 'in_stock';
        }

        if ((clone $productsQuery)->where('stock', '<=', 0)->exists()) {
            $availability[] = 'out_of_stock';
        }

        return $availability;
    }

    public static function getGenders($catIds)
    {
        if (empty($catIds) || !Schema::hasColumn('products', 'gender')) {
            return [];
        }

        return Product::select('gender')
            ->whereIn('category_id', $catIds)
            ->whereNotNull('gender')
            ->where('gender', '!=', '')
            ->groupBy('gender')
            ->pluck('gender')
            ->toArray();
    }

    public static function getColors($catIds)
    {
        if (empty($catIds)) {
            return [];
        }

        return Product::select('family_color')
            ->whereIn('category_id', $catIds)
            ->whereNotNull('family_color')
            ->where('family_color', '!=', '')
            ->groupBy('family_color')
            ->pluck('family_color')
            ->toArray();
    }

    public static function getSizes($catIds)
    {
        if (empty($catIds)) {
            return [];
        }

        $getProductsIds = Product::select('id')
        ->whereIn('category_id', $catIds)
        ->pluck('id')->toArray();

        if (empty($getProductsIds)) {
            return [];
        }

        return ProductsAttribute::select('size')
            ->where('status', 1)
            ->whereIn('product_id', $getProductsIds)
            ->whereNotNull('size')
            ->where('size', '!=', '')
            ->groupBy('size')
            ->pluck('size')
            ->toArray();
    }

    public static function getOccasions($catIds)
    {
        if (empty($catIds) || !Schema::hasColumn('products', 'occasion')) {
            return [];
        }

        $occasionRows = Product::select('occasion')
            ->whereIn('category_id', $catIds)
            ->whereNotNull('occasion')
            ->where('occasion', '!=', '')
            ->pluck('occasion')
            ->toArray();

        $occasions = [];
        foreach ($occasionRows as $occasionRow) {
            $values = preg_split('/\s*,\s*/', strtolower((string) $occasionRow), -1, PREG_SPLIT_NO_EMPTY);
            foreach ($values as $value) {
                $occasions[] = trim($value);
            }
        }

        return array_values(array_unique(array_filter($occasions)));
    }

    public static function getBrands($catIds)
    {
        if (empty($catIds)) {
            return [];
        }

        $getProductsIds = Product::select('id')
            ->whereIn('category_id', $catIds)
            ->pluck('id')->toArray();

        if (empty($getProductsIds)) {
            return [];
        }

        $getProductBrandIds = Product::select('brand_id')
            ->whereIn('id', $getProductsIds)
            ->whereNotNull('brand_id')
            ->groupBy('brand_id')
            ->pluck('brand_id')
            ->toArray();

        if (empty($getProductBrandIds)) {
            return [];
        }

        $getProductBrands = Brand::select('id', 'name')
            ->where('status', 1)
            ->whereIn('id', $getProductBrandIds)
            ->orderBy('name', 'asc')
            ->get()
            ->toArray();

        return $getProductBrands ?? [];
    }
}
