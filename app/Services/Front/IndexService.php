<?php

namespace App\Services\Front;

use App\Models\Banner;
use App\Models\Product;
use App\Models\Category;

class IndexService
{
    public function getHomePageBanners()
    {
        $homeSliderBanners = Banner::where('type', 'Slider')
            ->where('status', 1)
            ->orderBy('sort', 'Desc')
            ->get()
            ->toArray();
        $homeFixBanners = Banner::where('type', 'Fix')
            ->where('status', 1)
            ->orderBy('sort', 'Desc')
            ->get()
            ->toArray();
        $logoBanners = Banner::where('type', 'Logo')
            ->where('status', 1)
            ->orderBy('sort', 'Desc')
            ->get()
            ->toArray();
        return compact('homeSliderBanners', 'homeFixBanners', 'logoBanners');
    }

    public function featuredProducts()
    {
        $featuredProducts = Product::select('id', 'category_id', 'product_name','discount_applied_on',
        'product_price', 'product_discount', 'final_price','group_code','main_image')
            ->with(['product_images'])
            ->where(['is_featured'=>'Yes', 'status'=>1])
            ->where('stock','>',0)
            ->inRandomOrder()
            ->limit(8)
            ->get()
            ->toArray();
        return compact('featuredProducts');
    }

    public function newArrivalProducts()
    {
        $newArrivalProducts = Product::select('id', 'category_id', 'product_name', 'discount_applied_on',
        'product_price', 'product_discount', 'final_price', 'group_code', 'main_image')
            ->with(['product_images'])
            ->where('status', 1)
            ->where('stock', '>', 0)
            ->latest()
            ->orderBy('id', 'Desc')
            ->limit(8)
            ->get()
            ->toArray();
        return compact('newArrivalProducts');
    }

    public function homeCategories()
    {
        $categories=Category::select('id', 'name', 'image', 'url')
            ->whereNull('parent_id')  // only fetch top-level (parent) categories
            ->where('status', 1)  // Only Active categories
            ->where('menu_status', 1)  // Only Categories marked to show on menu/homepage
            ->get()
            ->map(function($category){
                $allCategoryIds= $this->getAllCategoryIds($category->id); // Get all category IDs including subcategories
        $productCount=Product::whereIn('category_id', $allCategoryIds)
            ->where('status', 1)
            ->where('stock', '>', 0)
            ->count(); // Count products in these categories

            return [
                'id' => $category->id,
                'name' => $category->name,
                'image' => $category->image,
                'url' => $category->url,
                'product_count' => $productCount, // Add product count to the category data
            ];
        });
        return['categories'=>$categories->toArray()];
    }

    Private function getAllCategoryIds($parentId)
    {
        $categoryIds = [$parentId]; // start with the current parent category
        $childIds = Category::where('parent_id', $parentId)
        ->where('status', 1)
        ->pluck('id'); // Get child category IDs
        foreach ($childIds as $childId) {
            $categoryIds = array_merge($categoryIds, $this->getAllCategoryIds($childId)); // Recursively get IDs of subcategories
        }
        return $categoryIds; // Return all category IDs (parent + children)
    }
}
