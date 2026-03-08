<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Filter extends Model
{
    protected $fillable = ['filter_name', 'filter_column', 'sort', 'status'];

    public function values()
    {
        return $this->hasMany(FilterValue::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_filter');
    }
}
