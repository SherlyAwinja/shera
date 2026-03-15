<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use Searchable;

    protected $casts = [
        'dimensions' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo('App\Models\Category', 'category_id')->with('parentCategory');
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
}
