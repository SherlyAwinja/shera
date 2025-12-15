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
        return $this->hasMany(ProductsImage::class);
    }

    public function attributes()
    {
        return $this->hasMany('App\Models\ProductsAttribute');
    }
}
