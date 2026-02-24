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

    public static function categoryDetails($url)
    {
        $category = self::with(['subcategories' => function($q){
            $q->with(['subcategories:id,parent_id,name']);
        }])
        ->where('url', $url)
        ->where('status', 1)
        ->first();

        if (!$category) return null;

        $catIds = [$category->id];

        // Add first level subcategories
        foreach ($category->subcategories as $subcat) {
            $catIds[] = $subcat->id;

            // Add second level subcategories (sub-subcategories)
            foreach ($subcat->subcategories as $subsubcat) {
                $catIds[] = $subsubcat->id;
            }
        }

        /*echo '<pre>';
        print_r($catIds);
        die;
        */

        $breadcrumbs = '<div class="px-2 py-1 mb-1" style="background-color: #f9f9f9;">';
        $breadcrumbs .='<nav aria-label="breadcrumb">';
        $breadcrumbs .='<ol class="breadcrumb mb-0" style="background-color: #f9f9f9; --bs-breadcrumb-divider: \'>\';">';
        $breadcrumbs .='<li class="breadcrumb-item"><a href="'.url('/').'" class="text-dark
        text-decoration-none">Home</a></li>';
        if($category->parent_id == 0) {
            $breadcrumbs .= '<li class="breadcrumb-item active" aria-current="page"><strong>' .$category->name . '</strong></li>';
        } else {
            $parentCategory = self::select('name', 'url')
                ->where('id', $category->parent_id)
                ->first();
        if ($parentCategory) {
            $breadcrumbs .= '<li class="breadcrumb-item"><a href="' . url($parentCategory->url) . '"
                class="text-dark text-decoration-none">' . $parentCategory->name . '</a></li>';
        }
        $breadcrumbs .= '<li class="breadcrumb-item active" aria-current="page"><strong>' . $category->name . '</strong></li>';
        }
        $breadcrumbs .='</ol>';
        $breadcrumbs .='</nav>';
        $breadcrumbs .='</div>';

        return [
            'catIds' => $catIds,
            'categoryDetails' => $category,
            'breadcrumbs' => $breadcrumbs,
        ];
    }
}
