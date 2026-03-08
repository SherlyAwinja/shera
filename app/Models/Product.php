<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
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
}
