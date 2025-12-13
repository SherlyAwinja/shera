<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public function parentCategory()
    {
        return $this->hasOne(Category::class, 'id', 'parent_id')
            ->select('id', 'name', 'url')
            ->where('status', 1)
            ->orderBy('id', 'ASC');
    }

    public function subcategories()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id')->where('status', 1);
            
    }

    public static function getCategories($type)
    {
        $getCategories = Category::with(['subcategories.subcategories'])
        ->where('parent_id', NULL)
        ->where('status', 1);

        if($type == 'Front'){
            $getCategories = $getCategories->where('menu_status', 1);
        }

        return $getCategories->get()->toArray();
    }
}
