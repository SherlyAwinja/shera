<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsCategory extends Model
{
    protected $table = 'products_categories';

    protected $fillable = [
        'product_id',
        'category_id',
    ];
}
