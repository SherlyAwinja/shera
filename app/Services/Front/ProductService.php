<?php

namespace App\Services\Front;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductVariant;
use App\Models\ProductsAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Filter;


class ProductService
{
    private function detailProductRelations(): array
    {
        return [
            'category.parentcategory',
            'attributes' => function ($query) {
                $query->where('status', 1)->orderBy('sort', 'asc');
            },
            'productVariants' => function ($query) {
                $query->orderBy('color', 'asc')->orderBy('size', 'asc');
            },
            'brand',
            'product_images' => function ($query) {
                $query->where('status', 1)->orderBy('sort', 'asc');
            },
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
            $attributeProductIds = ProductsAttribute::select('product_id')
                ->whereIn('size', $sizes)
                ->where('status', 1)
                ->pluck('product_id')
                ->toArray();
            $variantProductIds = ProductVariant::select('product_id')
                ->whereIn('size', $sizes)
                ->pluck('product_id')
                ->toArray();
            $getProductIds = array_values(array_unique(array_merge($attributeProductIds, $variantProductIds)));

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

    public function getProductDetailByUrl(string $url): ?Product
    {
        $product = Product::with($this->detailProductRelations())
        ->where('product_url', $url)
        ->where('status', 1)
        ->first();

        if ($product) {
            $product->color_variants = $this->buildColorVariants($product);
            $product->group_products = $product->color_variants;
        }

        if ($product) {
            $product->similar_products = $this->buildSimilarProducts($product);
        } else {
            if ($product) {
                $product->similar_products = collect();
            }
        }

        return $product;
    }

    private function buildSimilarProducts(Product $product)
    {
        $categoryIds = array_values(array_unique(array_filter([
            $product->category_id,
            optional($product->category)->parent_id,
            optional(optional($product->category)->parentcategory)->id,
        ])));

        if (empty($categoryIds)) {
            return collect();
        }

        return Product::with([
                'product_images' => function ($query) {
                    $query->where('status', 1)->orderBy('sort', 'asc');
                },
                'productVariants' => function ($query) {
                    $query->orderBy('color', 'asc')->orderBy('size', 'asc');
                },
                'attributes' => function ($query) {
                    $query->where('status', 1)->orderBy('sort', 'asc');
                },
            ])
            ->where('status', 1)
            ->where('id', '!=', $product->id)
            ->whereIn('category_id', $categoryIds)
            ->where(function ($query) {
                $query->where('stock', '>', 0)
                    ->orWhereHas('productVariants', function ($variantQuery) {
                        $variantQuery->where('stock', '>', 0);
                    })
                    ->orWhereHas('attributes', function ($attributeQuery) {
                        $attributeQuery->where('status', 1)->where('stock', '>', 0);
                    });
            })
            ->orderByRaw(
                'CASE WHEN category_id = ? THEN 0 ELSE 1 END',
                [$product->category_id]
            )
            ->orderByDesc('created_at')
            ->take(6)
            ->get()
            ->map(function (Product $similar) {
                if ($this->hasCanonicalVariants($similar)) {
                    $inStockVariants = $similar->productVariants
                        ->filter(fn (ProductVariant $variant) => (int) $variant->stock > 0)
                        ->values();
                    $sizeLabels = collect($this->uniqueLabeledValues($similar->productVariants->pluck('size')->all()));
                    $quickAddVariant = $inStockVariants->count() === 1 ? $inStockVariants->first() : null;

                    $similar->has_selectable_sizes = $sizeLabels->isNotEmpty();
                    $similar->in_stock_attribute_count = $inStockVariants->count();
                    $similar->quick_add_size = $quickAddVariant?->size;
                    $similar->quick_add_color = $quickAddVariant?->color;
                    $similar->can_quick_add = $quickAddVariant !== null;
                    $similar->is_available = $inStockVariants->isNotEmpty();

                    return $similar;
                }

                $activeAttributes = $similar->attributes
                    ->where('status', 1)
                    ->sortBy('sort')
                    ->values();
                $inStockAttributes = $activeAttributes
                    ->filter(fn (ProductsAttribute $attribute) => (int) $attribute->stock > 0)
                    ->values();
                $hasSelectableSizes = $activeAttributes->isNotEmpty();
                $availableColors = $this->productColorLabels($similar);
                $quickAddSize = null;
                $quickAddColor = count($availableColors) === 1 ? $availableColors[0] : null;
                $canQuickAdd = false;

                if ($hasSelectableSizes) {
                    if ($inStockAttributes->count() === 1) {
                        $quickAddSize = (string) $inStockAttributes->first()->size;
                        $canQuickAdd = count($availableColors) <= 1;
                    }
                } else {
                    $quickAddSize = 'NA';
                    $canQuickAdd = (int) $similar->stock > 0 && count($availableColors) <= 1;
                }

                $similar->has_selectable_sizes = $hasSelectableSizes;
                $similar->in_stock_attribute_count = $inStockAttributes->count();
                $similar->quick_add_size = $quickAddSize;
                $similar->quick_add_color = $quickAddColor;
                $similar->can_quick_add = $canQuickAdd;
                $similar->is_available = $hasSelectableSizes
                    ? $inStockAttributes->isNotEmpty()
                    : (int) $similar->stock > 0;

                return $similar;
            })
            ->values();
    }

    public function buildVariantState(Product $product, ?string $preferredSize = null, ?string $preferredColor = null): array
    {
        if (!$product->relationLoaded('attributes')) {
            $product->load([
                'attributes' => function ($query) {
                    $query->where('status', 1)->orderBy('sort', 'asc');
                },
            ]);
        }

        if (!$product->relationLoaded('productVariants')) {
            $product->load([
                'productVariants' => function ($query) {
                    $query->orderBy('color', 'asc')->orderBy('size', 'asc');
                },
            ]);
        }

        if (!$product->relationLoaded('product_images')) {
            $product->load([
                'product_images' => function ($query) {
                    $query->where('status', 1)->orderBy('sort', 'asc');
                },
            ]);
        }

        if ($this->hasCanonicalVariants($product)) {
            $selectedColor = $this->resolveSelectedColorLabel($product, $preferredColor);
            $selectedVariant = $this->resolveSelectedProductVariant($product, $selectedColor, $preferredSize);
            $pricing = $selectedVariant
                ? Product::getAttributePrice($product->id, $selectedVariant->size, $selectedVariant->color)
                : $this->baseProductPricing($product);
            $stock = $this->buildCanonicalStockState($selectedVariant);
            $sizes = $this->variantsForColor($product, $selectedColor)
                ->map(function (ProductVariant $variant) use ($selectedVariant) {
                    return [
                        'id' => (int) $variant->id,
                        'size' => (string) $variant->size,
                        'stock' => (int) $variant->stock,
                        'in_stock' => (int) $variant->stock > 0,
                        'checked' => $selectedVariant !== null && (int) $selectedVariant->id === (int) $variant->id,
                    ];
                })
                ->values()
                ->all();

            return [
                'product_id' => (int) $product->id,
                'product_name' => (string) $product->product_name,
                'product_url' => !empty($product->product_url) ? url($product->product_url) : null,
                'color' => $selectedColor ?? '',
                'image' => $this->resolveProductImageUrl($product, $selectedColor),
                'product_price' => (int) ($pricing['product_price'] ?? 0),
                'final_price' => (int) ($pricing['final_price'] ?? 0),
                'percent' => (int) ($pricing['percent'] ?? 0),
                'has_discount' => (int) ($pricing['percent'] ?? 0) > 0
                    && (int) ($pricing['final_price'] ?? 0) < (int) ($pricing['product_price'] ?? 0),
                'selected_size' => $selectedVariant?->size,
                'sizes' => $sizes,
                'has_sizes' => !empty($sizes),
                'stock' => $stock['stock'],
                'in_stock' => $stock['in_stock'],
                'stock_label' => $stock['stock_label'],
                'stock_message' => $stock['stock_message'],
                'can_purchase' => $stock['in_stock'],
            ];
        }

        $selectedColor = $this->resolveSelectedColorLabel($product, $preferredColor);
        $selectedAttribute = $this->resolveSelectedAttribute($product, $preferredSize);
        $pricing = $selectedAttribute
            ? Product::getAttributePrice($product->id, $selectedAttribute->size, $selectedColor)
            : $this->baseProductPricing($product);
        $stock = $this->buildStockState($product, $selectedAttribute, $selectedColor);
        $selectedSize = $selectedAttribute ? $selectedAttribute->size : null;

        return [
            'product_id' => (int) $product->id,
            'product_name' => (string) $product->product_name,
            'product_url' => !empty($product->product_url) ? url($product->product_url) : null,
            'color' => $selectedColor ?? '',
            'image' => $this->resolveProductImageUrl($product, $selectedColor),
            'product_price' => (int) ($pricing['product_price'] ?? 0),
            'final_price' => (int) ($pricing['final_price'] ?? 0),
            'percent' => (int) ($pricing['percent'] ?? 0),
            'has_discount' => (int) ($pricing['percent'] ?? 0) > 0
                && (int) ($pricing['final_price'] ?? 0) < (int) ($pricing['product_price'] ?? 0),
            'selected_size' => $selectedSize,
            'sizes' => $product->attributes
                ->map(function (ProductsAttribute $attribute) use ($selectedSize) {
                    return [
                        'id' => (int) $attribute->id,
                        'size' => (string) $attribute->size,
                        'stock' => (int) $attribute->stock,
                        'in_stock' => (int) $attribute->stock > 0,
                        'checked' => $selectedSize === $attribute->size,
                    ];
                })
                ->values()
                ->all(),
            'has_sizes' => $product->attributes->isNotEmpty(),
            'stock' => $stock['stock'],
            'in_stock' => $stock['in_stock'],
            'stock_label' => $stock['stock_label'],
            'stock_message' => $stock['stock_message'],
            'can_purchase' => $stock['in_stock'],
        ];
    }

    public function getProductVariantData(int $productId, string $color, ?string $preferredSize = null): array
    {
        $product = Product::with($this->detailProductRelations())
            ->where('id', $productId)
            ->where('status', 1)
            ->first();

        if (!$product) {
            return [
                'status' => false,
                'message' => 'The requested product could not be found.',
            ];
        }

        if (!$this->productHasColor($product, $color)) {
            return [
                'status' => false,
                'message' => 'The selected color is not assigned to this product.',
            ];
        }

        return array_merge(
            [
                'status' => true,
                'message' => 'Product color loaded successfully.',
            ],
            $this->buildVariantState($product, $preferredSize, $color)
        );
    }

    private function buildColorVariants(Product $product)
    {
        $currentSelectedColor = $this->resolveSelectedColorLabel($product);

        if (!$product->relationLoaded('product_images')) {
            $product->load([
                'product_images' => function ($query) {
                    $query->where('status', 1)->orderBy('sort', 'asc');
                },
            ]);
        }

        return collect($this->productColorLabels($product))
            ->map(function ($label) use ($product, $currentSelectedColor) {
                return (object) [
                    'id' => (int) $product->id,
                    'product_url' => $product->product_url,
                    'product_name' => $product->product_name,
                    'product_color' => $product->product_color,
                    'group_code' => $product->group_code,
                    'family_color' => (string) $product->product_color,
                    'color_labels' => [$label],
                    'color_display' => $label,
                    'swatch_background' => $this->resolveSwatchColor($label),
                    'image_url' => $this->resolveProductImageUrl($product, $label),
                    'is_current' => $this->colorsMatch($label, $currentSelectedColor),
                ];
            })
            ->values();
    }

    private function parseProductColors(string $rawColors): array
    {
        $parts = preg_split('/\s*,\s*/', $rawColors, -1, PREG_SPLIT_NO_EMPTY);
        return $this->uniqueLabeledValues($parts);
    }

    private function resolveSelectedColorLabel(Product $product, ?string $preferredColor = null): ?string
    {
        $labels = $this->productColorLabels($product);

        if (empty($labels)) {
            return null;
        }

        if (!empty($preferredColor)) {
            foreach ($labels as $label) {
                if ($this->colorsMatch($label, $preferredColor)) {
                    return $label;
                }
            }
        }

        if ($this->hasCanonicalVariants($product)) {
            $firstInStockVariant = $product->productVariants->first(function (ProductVariant $variant) {
                return (int) $variant->stock > 0;
            });

            if ($firstInStockVariant) {
                return $firstInStockVariant->color;
            }

            return $labels[0];
        }

        $firstInStock = collect($labels)->first(function (string $label) use ($product) {
            $colorStock = $this->resolveColorStockValue($product, $label);

            return $colorStock !== null && $colorStock > 0;
        });

        if ($firstInStock) {
            return $firstInStock;
        }

        return $labels[0];
    }

    private function productHasColor(Product $product, string $color): bool
    {
        foreach ($this->productColorLabels($product) as $label) {
            if ($this->colorsMatch($label, $color)) {
                return true;
            }
        }

        return false;
    }

    private function productColorLabels(Product $product): array
    {
        if ($this->hasCanonicalVariants($product)) {
            return $this->uniqueLabeledValues($product->productVariants->pluck('color')->all());
        }

        return $this->parseProductColors((string) $product->product_color);
    }

    private function uniqueLabeledValues(array $values): array
    {
        $labels = [];
        $seen = [];

        foreach ($values as $value) {
            $label = trim((string) $value);
            if ($label === '') {
                continue;
            }

            $normalized = strtolower($label);
            if (isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $labels[] = $label;
        }

        return $labels;
    }

    private function hasCanonicalVariants(Product $product): bool
    {
        return $product->relationLoaded('productVariants')
            ? $product->productVariants->isNotEmpty()
            : $product->productVariants()->exists();
    }

    private function variantsForColor(Product $product, ?string $preferredColor = null)
    {
        $variants = $product->productVariants;

        if (empty($preferredColor)) {
            return $variants->values();
        }

        return $variants
            ->filter(function (ProductVariant $variant) use ($preferredColor) {
                return $this->colorsMatch($variant->color, $preferredColor);
            })
            ->values();
    }

    private function resolveSelectedProductVariant(Product $product, ?string $preferredColor = null, ?string $preferredSize = null): ?ProductVariant
    {
        $variants = $this->variantsForColor($product, $preferredColor);

        if ($variants->isEmpty()) {
            $variants = $product->productVariants->values();
        }

        $preferredVariant = null;

        if (!empty($preferredSize)) {
            $preferredVariant = $variants->first(function (ProductVariant $variant) use ($preferredSize) {
                return strtolower(trim((string) $variant->size)) === strtolower(trim((string) $preferredSize));
            });

            if ($preferredVariant && (int) $preferredVariant->stock > 0) {
                return $preferredVariant;
            }
        }

        $firstInStock = $variants->first(function (ProductVariant $variant) {
            return (int) $variant->stock > 0;
        });

        return $firstInStock ?: $preferredVariant ?: $variants->first();
    }

    private function buildCanonicalStockState(?ProductVariant $variant): array
    {
        $stock = $variant ? max(0, (int) $variant->stock) : 0;
        $inStock = $stock > 0;
        $descriptor = $variant
            ? trim(sprintf('%s / %s', $variant->color, $variant->size), ' /')
            : 'This combination';

        return [
            'stock' => $stock,
            'in_stock' => $inStock,
            'stock_label' => $inStock ? 'In stock' : 'Out of stock',
            'stock_message' => $inStock
                ? sprintf('%d unit%s available in %s.', $stock, $stock === 1 ? '' : 's', $descriptor)
                : sprintf('%s is currently out of stock.', $descriptor),
        ];
    }

    private function colorsMatch(?string $left, ?string $right): bool
    {
        return strtolower(trim((string) $left)) === strtolower(trim((string) $right));
    }

    private function resolveSwatchColor(string $label): string
    {
        $normalized = strtolower(trim($label));

        $palette = [
            'azure' => '#007FFF',
            'beige' => '#F5F5DC',
            'black' => '#111111',
            'blush' => '#DE8FA1',
            'blue' => '#2563EB',
            'bronze' => '#CD7F32',
            'brown' => '#8B5E3C',
            'burgundy' => '#800020',
            'camel' => '#C19A6B',
            'charcoal' => '#36454F',
            'chocolate' => '#7B3F00',
            'copper' => '#B87333',
            'coral' => '#FF7F50',
            'cream' => '#FFF5CC',
            'cyan' => '#00BCD4',
            'denim' => '#1560BD',
            'emerald' => '#50C878',
            'fuchsia' => '#FF00FF',
            'gold' => '#D4AF37',
            'gray' => '#808080',
            'grey' => '#808080',
            'green' => '#2E8B57',
            'indigo' => '#4B0082',
            'ivory' => '#FFFFF0',
            'jade' => '#00A86B',
            'khaki' => '#C3B091',
            'lavender' => '#B57EDC',
            'lemon' => '#FDE910',
            'lime' => '#A4C639',
            'lilac' => '#C8A2C8',
            'magenta' => '#D0006F',
            'maroon' => '#800000',
            'mauve' => '#B784A7',
            'mint' => '#98FF98',
            'multi' => 'linear-gradient(135deg, #EF4444 0%, #F59E0B 25%, #10B981 50%, #3B82F6 75%, #8B5CF6 100%)',
            'mustard' => '#D4A017',
            'navy' => '#1E3A5F',
            'neon' => '#39FF14',
            'ochre' => '#CC7722',
            'olive' => '#708238',
            'onyx' => '#353839',
            'orange' => '#F97316',
            'pastel' => '#AEC6CF',
            'peach' => '#FFCBA4',
            'pink' => '#EC4899',
            'plum' => '#8E4585',
            'purple' => '#7C3AED',
            'red' => '#DC2626',
            'rose' => '#F43F5E',
            'ruby' => '#E0115F',
            'rust' => '#B7410E',
            'saffron' => '#F4C430',
            'sage' => '#9CAF88',
            'sand' => '#C2B280',
            'scarlet' => '#FF2400',
            'silver' => '#C0C0C0',
            'slate' => '#708090',
            'tan' => '#D2B48C',
            'taupe' => '#8B7E74',
            'teal' => '#0F766E',
            'topaz' => '#FFC857',
            'turquoise' => '#40E0D0',
            'violet' => '#7F00FF',
            'white' => '#FFFFFF',
            'wine' => '#722F37',
            'yellow' => '#FACC15',
        ];

        if (isset($palette[$normalized])) {
            return $palette[$normalized];
        }

        if (preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $normalized)) {
            return $normalized;
        }

        return $this->fallbackSwatchColor($normalized);
    }

    private function buildSwatchBackground(array $colors): string
    {
        if (empty($colors)) {
            return '#D1D5DB';
        }

        if (count($colors) === 1) {
            return $colors[0];
        }

        $segments = [];
        $count = count($colors);

        foreach ($colors as $index => $color) {
            $start = (100 / $count) * $index;
            $end = (100 / $count) * ($index + 1);
            $segments[] = sprintf('%s %.2f%%, %s %.2f%%', $color, $start, $color, $end);
        }

        return 'linear-gradient(135deg, ' . implode(', ', $segments) . ')';
    }

    private function fallbackSwatchColor(string $label): string
    {
        $hash = abs(crc32($label));
        $red = 70 + ($hash & 0x7F);
        $green = 70 + (($hash >> 8) & 0x7F);
        $blue = 70 + (($hash >> 16) & 0x7F);

        return sprintf('#%02X%02X%02X', $red, $green, $blue);
    }

    private function resolveSelectedAttribute(Product $product, ?string $preferredSize = null): ?ProductsAttribute
    {
        $attributes = $product->attributes;

        if ($attributes->isEmpty()) {
            return null;
        }

        if (!empty($preferredSize)) {
            $preferred = $attributes->firstWhere('size', $preferredSize);

            if ($preferred) {
                return $preferred;
            }
        }

        $firstInStock = $attributes->first(function (ProductsAttribute $attribute) {
            return (int) $attribute->stock > 0;
        });

        return $firstInStock ?: $attributes->first();
    }

    private function baseProductPricing(Product $product): array
    {
        $pricing = $this->computeInitialPrice($product);

        return [
            'product_price' => (int) $pricing['base_price'],
            'final_price' => (int) $pricing['final_price'],
            'percent' => (int) $pricing['discount_percent'],
        ];
    }

    private function buildStockState(Product $product, ?ProductsAttribute $attribute = null, ?string $selectedColor = null): array
    {
        if ($attribute) {
            $stock = (int) $attribute->stock;
            $inStock = $stock > 0;
            $message = $inStock
                ? sprintf('%d unit%s available in size %s.', $stock, $stock === 1 ? '' : 's', $attribute->size)
                : sprintf('Size %s is currently out of stock.', $attribute->size);

            return [
                'stock' => $stock,
                'in_stock' => $inStock,
                'stock_label' => $inStock ? 'In stock' : 'Out of stock',
                'stock_message' => $message,
            ];
        }

        $colorStock = $this->resolveColorStockValue($product, $selectedColor);

        if ($colorStock !== null) {
            $stock = $colorStock;
            $inStock = $stock > 0;
            $message = $inStock
                ? sprintf('%d unit%s available in %s.', $stock, $stock === 1 ? '' : 's', $selectedColor)
                : sprintf('%s is currently out of stock.', $selectedColor);
        } else {
            $stock = (int) $product->stock;
            $inStock = $stock > 0;
            $message = $inStock
                ? sprintf('%d unit%s available.', $stock, $stock === 1 ? '' : 's')
                : 'This variant is currently out of stock.';
        }

        return [
            'stock' => $stock,
            'in_stock' => $inStock,
            'stock_label' => $inStock ? 'In stock' : 'Out of stock',
            'stock_message' => $message,
        ];
    }

    private function resolveColorStockValue(Product $product, ?string $preferredColor = null): ?int
    {
        if (empty($preferredColor)) {
            return null;
        }

        $colorStock = $product->color_stock;

        if (!is_array($colorStock) || empty($colorStock)) {
            return null;
        }

        foreach ($colorStock as $label => $stock) {
            if ($this->colorsMatch((string) $label, $preferredColor)) {
                return max(0, (int) $stock);
            }
        }

        return null;
    }

    private function resolveProductImageUrl(Product $product, ?string $preferredColor = null): string
    {
        $matchedImage = $this->resolveColorAssignedImageName($product, $preferredColor);

        if ($matchedImage !== null) {
            return asset('front/images/products/' . $matchedImage);
        }

        if (!empty($product->main_image)) {
            return asset('front/images/products/' . $product->main_image);
        }

        $firstImage = $product->product_images->first();

        if ($firstImage && !empty($firstImage->image)) {
            return asset('front/images/products/' . $firstImage->image);
        }

        return asset('front/images/products/no-image.jpg');
    }

    private function resolveColorAssignedImageName(Product $product, ?string $preferredColor = null): ?string
    {
        $normalizedColor = strtolower(trim((string) $preferredColor));

        if ($normalizedColor === '') {
            return null;
        }

        foreach ($product->product_images as $image) {
            if (!empty($image->image) && $this->colorsMatch((string) ($image->color ?? ''), $normalizedColor)) {
                return $image->image;
            }
        }

        $colorToken = preg_replace('/[^a-z0-9]+/i', '', $normalizedColor);

        if ($colorToken === '') {
            return null;
        }

        $imageCandidates = [];

        if (!empty($product->main_image)) {
            $imageCandidates[] = $product->main_image;
        }

        foreach ($product->product_images as $image) {
            if (!empty($image->image)) {
                $imageCandidates[] = $image->image;
            }
        }

        foreach ($imageCandidates as $candidate) {
            $candidateToken = preg_replace('/[^a-z0-9]+/i', '', strtolower((string) $candidate));

            if ($candidateToken !== '' && str_contains($candidateToken, $colorToken)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Compute the initial price to show on page load
     * - Uses first active size price if attributes exist; otherwise product_price.
     * - Applies discount priority: product > category > brand
    */
    public function computeInitialPrice(Product $product): array
    {
        // Base price: first attribute OR product base price
        $firstAttr = $product->attributes->first();
        $basePrice = $firstAttr ? (float) $firstAttr->price : (float) $product->product_price;

        // Discounts
        $productDiscount = (float) ($product->product_discount ?? 0);

        $categoryDiscount = 0.0;
        if ($product->category) {
            $categoryDiscount = (float) (
                $product->category->discount ??
                $product->category->category_discount ??
                0
            );
        }

        $brandDiscount = 0.0;
        if ($product->brand) {
            $brandDiscount = (float) ($product->brand->discount ?? 0);
        }

        // Priority: product > category > brand
        $applied = 0.0;

        if ($productDiscount > 0) {
            $applied = $productDiscount;
        } elseif ($categoryDiscount > 0) {
            $applied = $categoryDiscount;
        } elseif ($brandDiscount > 0) {
            $applied = $brandDiscount;
        }

        // Final price
        $final = round($basePrice * (1 - $applied / 100));

        $hasDiscount = $applied > 0 && $final < $basePrice;

        return [
            'base_price' => (int) $basePrice,
            'final_price' => (int) $final,
            'discount_percent' => (int) $applied,
            'has_discount' => $hasDiscount,
            'preselected_size' => $firstAttr ? $firstAttr->size : null,
        ];
    }
}
