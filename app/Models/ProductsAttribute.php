<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsAttribute extends Model
{
    protected $fillable = [
        'product_id',
        'size',
        'sku',
        'price',
        'stock',
        'sort',
        'status'
    ];

    public static function productStock($product_id, $size)
    {
        return self::where(['product_id'=>$product_id,'size'=>$size, 'status'=>1])->value('stock') ?? 0;
    }
}
