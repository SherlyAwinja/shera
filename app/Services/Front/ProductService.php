<?php

namespace App\Services\Front;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\View;


class ProductService
{
    public function getCategotyListingData($url)
    {
        $categoryInfo = Category::categoryDetails($url);

        $query = Product::with(['product_images'])
            ->whereIn('category_id', $categoryInfo['catIds'])
            ->where('status', 1);

            // Apply filters (sort)
        $query = $this->applyFilters($query);

        $products = $query->paginate(3)->withQueryString();

        return [
            'categoryDetails' => $categoryInfo['categoryDetails'],
            'categoryProducts' => $products,
            'breadcrumbs' => $categoryInfo['breadcrumbs'],
            'selectedSort' => request()->get('sort', ''),
            'url' => $url
        ];
    }

    private function applyFilters($query)
    {
        $sort = request()->get('sort');

        switch ($sort) {
            case 'product_latest':
                $query->orderBy('created_at', 'DESC');
                break;
            case 'lowest_price':
                $query->orderBy('final_price', 'ASC');
                break;
            case 'highest_price':
                $query->orderBy('final_price', 'DESC');
                break;
            case 'best_selling':
                $query->inRandomOrder(); // Temporary untill sales data is added
                break;
            case 'discountented_items':
                $query->where('product_discount', '>', 0);
                break;
            case 'featured_items':
                $query->where('is_featured', 'Yes')->orderBy('created_at', 'DESC');
                break;
            default:
                $query->orderBy('created_at', 'DESC');
        }

        return $query;
    }
}
