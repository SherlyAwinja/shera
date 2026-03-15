<?php

namespace App\Services\Front;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductsAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Filter;


class ProductService
{
    public function getCategotyListingDataOld($url)
    {
        $categoryInfo = Category::categoryDetails($url);

        $query = Product::with(['product_images'])
            ->whereIn('category_id', $categoryInfo['catIds'])
            ->where('status', 1);

            // Apply filters (sort)
        $query = $this->applyFilters($query);

        $products = $query->paginate(9)->withQueryString();

        // Fetch filters with values
        $filters = Filter::with(['values' =>function($q){
            $q->where('status', 1)->orderBy('sort', 'asc');
        }])->where('status', 1)->orderBy('sort', 'asc')->get();

        return [
            'categoryDetails' => $categoryInfo['categoryDetails'],
            'categoryProducts' => $products,
            'breadcrumbs' => $categoryInfo['breadcrumbs'],
            'selectedSort' => request()->get('sort', ''),
            'url' => $url,
            'catIds' => $categoryInfo['catIds'],
            'filters' => $filters,
        ];
    }

    public function getCategoryListingData($url)
    {
        // Get category details
        $categoryInfo = Category::categoryDetails($url);

        // All category + subcategory IDs
        $catIds = $categoryInfo['catIds'] ?? [];

        // Build the product query
        $query = Product::with(['product_images'])
            ->where('status', 1)
            ->where(function ($q) use ($catIds) {
                // Condition 1: product's main category
                $q->whereIn('category_id', $catIds)
                // Condition 2: product assigned via pivot table products_categories
                ->orWhereHas('categories', function ($subQ) use ($catIds) {
                    $subQ->whereIn('categories.id', $catIds);
                });
            });

        // Apply additional filters (color, size, brand, dynamic filters, etc.)
        $query = $this->applyFilters($query);

        // Paginate products with query string
        $products = $query->paginate(8)->withQueryString();

        // Fetch filters with their values
        $filters = Filter::with(['values' => function($q) {
                $q->where('status', 1)->orderBy('sort', 'asc');
            }])
            ->where('status', 1)
            ->orderBy('sort', 'asc')
            ->get();

        // Return structured data for the view
        return [
            'categoryDetails'   => $categoryInfo['categoryDetails'] ?? null,
            'categoryProducts'  => $products,
            'breadcrumbs'       => $categoryInfo['breadcrumbs'] ?? [],
            'selectedSort'      => request()->get('sort', 'product_latest'),
            'url'               => $url,
            'catIds'            => $catIds,
            'filters'           => $filters,
        ];
    }

    // Backward-compatible alias (typo kept for existing controller usage).
    public function getCategotyListingData($url)
    {
        return $this->getCategoryListingData($url);
    }

    private function applyFilters($query)
    {
        // Apply Sorting Filter
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
            case 'discount_products':
            case 'discountented_items':
                $query->where('product_discount', '>', 0);
                break;
            case 'featured_products':
            case 'featured_items':
                $query->where('is_featured', 'Yes')->orderBy('created_at', 'DESC');
                break;
            default:
                $query->orderBy('created_at', 'DESC');
        }

        // Apply color filter
        $colors = $this->parseFilterValues('color');
        $colors = array_values(array_unique(array_filter(array_map('trim', $colors))));
        if (count($colors) > 0) {
            $normalizedProductColorColumn = "REPLACE(REPLACE(product_color, ', ', ','), ' ,', ',')";
            $query->where(function ($colorQuery) use ($colors, $normalizedProductColorColumn) {
                foreach ($colors as $color) {
                    $colorQuery->orWhereRaw("FIND_IN_SET(?, {$normalizedProductColorColumn}) > 0", [$color]);
                }
            });
        }

        // Apply size filter
        $sizes = $this->parseFilterValues('size');
        if (count($sizes) > 0) {
            $getProductIds = ProductsAttribute::select('product_id')
                ->whereIn('size', $sizes)
                ->where('status', 1)
                ->pluck('product_id')
                ->toArray();
            if (count($getProductIds) > 0) {
                $query->whereIn('id', $getProductIds);
            }
        }

        // Apply gender filter
        $genders = $this->normalizeFilterValues($this->parseFilterValues('gender'));
        if (count($genders) > 0 && Schema::hasColumn('products', 'gender')) {
            $query->where(function ($genderQuery) use ($genders) {
                $genderQuery->whereIn(DB::raw('LOWER(TRIM(gender))'), $genders);

                foreach ($genders as $gender) {
                    $keyword = $this->genderKeyword($gender);
                    if ($keyword === null) {
                        continue;
                    }

                    $genderQuery->orWhere(function ($fallbackQuery) use ($keyword) {
                        $fallbackQuery
                            ->where(function ($emptyGenderQuery) {
                                $emptyGenderQuery->whereNull('gender')->orWhereRaw("TRIM(gender) = ''");
                            })
                            ->whereRaw('LOWER(product_name) LIKE ?', ['%' . $keyword . '%']);
                    });
                }
            });
        }

        // Apply occasion filter
        $occasions = $this->normalizeOccasionValues($this->parseFilterValues('occasion'));
        if (count($occasions) > 0 && Schema::hasColumn('products', 'occasion')) {
            $query->where(function ($occasionQuery) use ($occasions) {
                $occasionQuery->where(function ($storedOccasionQuery) use ($occasions) {
                    foreach ($occasions as $occasion) {
                        $storedOccasionQuery->orWhereRaw(
                            "FIND_IN_SET(?, REPLACE(LOWER(COALESCE(occasion, '')), ' ', '')) > 0",
                            [$occasion]
                        );
                    }
                });

                foreach ($occasions as $occasion) {
                    $keyword = $this->occasionKeyword($occasion);
                    if ($keyword === null) {
                        continue;
                    }

                    $occasionQuery->orWhere(function ($fallbackQuery) use ($keyword) {
                        $fallbackQuery
                            ->where(function ($emptyOccasionQuery) {
                                $emptyOccasionQuery->whereNull('occasion')->orWhereRaw("TRIM(occasion) = ''");
                            })
                            ->whereRaw('LOWER(product_name) LIKE ?', ['%' . $keyword . '%']);
                    });
                }
            });
        }

        // Apply Availability filter
        $availabilities = $this->parseFilterValues('availability');
        if (count($availabilities) > 0) {
            $hasAvailabilityColumn = Schema::hasColumn('products', 'availability');
            $hasAvailabilityData = $hasAvailabilityColumn
                && (clone $query)->whereNotNull('availability')->where('availability', '!=', '')->exists();

            if ($hasAvailabilityData) {
                $query->whereIn('availability', $availabilities);
            } else {
                $allowInStock = in_array('in_stock', $availabilities, true);
                $allowOutOfStock = in_array('out_of_stock', $availabilities, true);

                if ($allowInStock || $allowOutOfStock) {
                    $query->where(function ($stockQuery) use ($allowInStock, $allowOutOfStock) {
                        if ($allowInStock) {
                            $stockQuery->orWhere('stock', '>', 0);
                        }

                        if ($allowOutOfStock) {
                            $stockQuery->orWhere('stock', '<=', 0);
                        }
                    });
                }
            }
        }

        // Apply Brand filter
        if (request()->has('brand') && !empty(request()->get('brand'))) {
            $brands = explode('~', request()->get('brand'));
            $getbrandIds = Brand::select('id')
                ->whereIn('name', $brands)
                ->pluck('id')
                ->toArray();
            $query->whereIn('brand_id', $getbrandIds);
        }

        // Apply Price filter
        if (request()->has('price') && !empty(request()->get('price'))) {
            $priceInput = str_replace("~","-", request()->get('price'));
            $prices = explode('-', $priceInput);
            $count = count($prices);
            if ($count >= 2) {
                $query->whereBetween('final_price', [(int)$prices[0], (int)$prices[$count-1]]);
            }
        }

        // Apply Category Filter
        if (request()->has('category') && !empty(request()->get('category'))) {
            // Get selected category IDs from request
            $categoryIds = explode('~', request()->get('category'));

            // Get IDs of child categories whose parent is in the selected categories
            $parentIds = Category::whereIn('parent_id', $categoryIds)
                                ->pluck('id')
                                ->toArray();

            // Merge parent and selected category IDs
            $allCatIds = array_merge($parentIds, $categoryIds);

            // Apply filter to the query if there are any categories
            if (!empty($allCatIds)) {
                $query->whereIn('category_id', $allCatIds);
            }
        }

        // Apply Dynamic Admin Filters
        $filterParams = request()->all();

        foreach ($filterParams as $filterKey => $filterValues) {
            // Skip known default filters
            if (in_array($filterKey, ['color', 'size', 'brand', 'price', 'gender', 'availability', 'occasion', 'sort', 'page', 'json', 'category', 'subcategory'], true)) {
                continue;
            }

            // Filter values may come as array or "~" separated string.
            $rawValues = is_array($filterValues) ? implode('~', $filterValues) : (string) $filterValues;
            $selectedValues = array_values(array_filter(array_map('trim', explode('~', $rawValues)), fn ($value) => $value !== ''));

            if (empty($selectedValues)) {
                continue;
            }

            $normalizedFilterKey = str_replace('[]', '', (string) $filterKey);
            $query->whereHas('filterValues', function ($q) use ($selectedValues, $normalizedFilterKey) {
                $q->whereIn('value', $selectedValues)
                    ->whereHas('filter', function ($filterQuery) use ($normalizedFilterKey) {
                        $filterQuery->where('filter_name', $normalizedFilterKey);
                    });
            });
        }

        return $query;
    }

    public function searchProducts($query, $limit = 6)
    {
        $terms = explode(' ', str_replace(['-', '_'], ' ', $query));
        $fullQuery = trim((string) $query);

        $searchQuery = Product::with([
            'product_images' => function ($q) {
                $q->where('status', 1)->orderBy('sort', 'asc');
            }
        ])
        ->where('status', 1)
        ->where('stock', '>', 0)
        ->where(function ($q) use ($terms, $fullQuery) {
            if ($fullQuery !== '') {
                $q->orWhere('search_keywords', 'LIKE', '%' . $fullQuery . '%');
            }
            foreach ($terms as $term) {
                if (!empty($term)) {
                    $q->orWhere('product_name', 'LIKE', '%' . $term . '%')
                    ->orWhere('product_code', 'LIKE', '%' . $term . '%')
                    ->orWhere('product_color', 'LIKE', '%' . $term . '%')
                    ->orWhere('search_keywords', 'LIKE', '%' . $term . '%');
                }
            }
        });

        if ($fullQuery !== '') {
            $fullQueryLike = '%' . $fullQuery . '%';
            $searchQuery->orderByRaw(
                "CASE
                    WHEN search_keywords LIKE ? THEN 0
                    WHEN product_name LIKE ? THEN 1
                    WHEN product_code LIKE ? THEN 2
                    WHEN product_color LIKE ? THEN 3
                    ELSE 4
                 END",
                [$fullQueryLike, $fullQueryLike, $fullQueryLike, $fullQueryLike]
            );
        }

        return $searchQuery
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    private function parseFilterValues(string $key): array
    {
        $rawValue = request()->get($key);

        if (empty($rawValue)) {
            return [];
        }

        $values = preg_split('/[~,]/', (string) $rawValue);

        return array_values(array_filter(array_map('trim', $values), fn ($value) => $value !== ''));
    }

    private function normalizeFilterValues(array $values): array
    {
        return array_values(array_unique(array_map(fn ($value) => strtolower(trim((string) $value)), $values)));
    }

    private function normalizeOccasionValues(array $values): array
    {
        $normalized = $this->normalizeFilterValues($values);

        if (in_array('cassual', $normalized, true) && !in_array('casual', $normalized, true)) {
            $normalized[] = 'casual';
        }

        if (in_array('casual', $normalized, true) && !in_array('cassual', $normalized, true)) {
            $normalized[] = 'cassual';
        }

        return array_values(array_unique($normalized));
    }

    private function genderKeyword(string $gender): ?string
    {
        $map = [
            'men' => 'men',
            'women' => 'women',
            'unisex' => 'unisex',
            'kids' => 'kids',
        ];

        return $map[$gender] ?? null;
    }

    private function occasionKeyword(string $occasion): ?string
    {
        $map = [
            'work' => 'work',
            'cassual' => 'casual',
            'casual' => 'casual',
            'travel' => 'travel',
            'gym' => 'gym',
        ];

        return $map[$occasion] ?? null;
    }

    private function dynamicFilterRequestKey(int $filterId): string
    {
        return 'filter_' . $filterId;
    }
}
