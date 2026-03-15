<!-- Shop Sidebar Start -->
@include('front.products.filters')
<!-- Shop Sidebar End -->

<!-- Shop Product Start -->
<div class="col-lg-9 col-md-12">
    <div class = "row pb-3">
        <div class = "col-12 pb-1">
            <div class = "mb-3">
                {!! $breadcrumbs ?? '' !!}
                <div class = "small text-muted">
                    (FOUND {{ $categoryProducts->total() }} RESULTS)
                </div>
            </div>
        </div>
    </div>
    <div class="row pb-3">
        <div class="col-12 pb-1">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <form action="">
                    <!-- <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search by name">
                        <div class="input-group-append">
                            <span class="input-group-text bg-transparent text-primary">
                            <i class="fa fa-search"></i>
                            </span>
                        </div>
                    </div> -->
                </form>
                <form name="sortProducts" id="sortProducts">
                    <input type="hidden" name="url" id="url" value="{{ $url }}">
                    <select class="form-control getsort" name = "sort" id = "sort">
                        <option value="">Sort by</option>
                        <option value="lowest_price" @if(request()->get('sort') == 'lowest_price') selected @endif>Sort by: Lowest Price</option>
                        <option value="highest_price" @if(request()->get('sort') == 'highest_price') selected @endif>Sort by: Highest Price</option>
                        <option value="product_latest" @if(request()->get('sort') == 'product_latest') selected @endif>Sort by: Latest Products</option>
                        <option value="best_selling" @if(request()->get('sort') == 'best_selling') selected @endif>Sort by: Best Selling</option>
                        <option value="discount_products" @if(request()->get('sort') == 'discount_products') selected @endif>Sort by: Discount Products</option>
                        <option value="featured_products" @if(request()->get('sort') == 'featured_products') selected @endif>Sort by: Featured Products</option>
                    </select>
                </form>
            </div>
        </div>
        @foreach($categoryProducts as $product)
            @php
                $fallbackImage = asset('front/images/products/no-image.jpg');
                $image = '';
                $selectedColors = request()->has('color')
                    ? preg_split('/[~,]/', request()->get('color'))
                    : [];
                $selectedColors = array_values(array_filter(array_map(
                    fn ($value) => strtolower(trim((string) $value)),
                    (array) $selectedColors
                )));

                $productColors = preg_split('/\s*,\s*/', (string) ($product['product_color'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
                $productColors = array_values(array_filter(array_map('trim', $productColors)));
                $productColorMap = array_map('strtolower', $productColors);

                $activeColor = null;
                foreach ($selectedColors as $color) {
                    if (in_array($color, $productColorMap, true)) {
                        $activeColor = $color;
                        break;
                    }
                }

                $activeColorLabel = null;
                if ($activeColor !== null) {
                    foreach ($productColors as $color) {
                        if (strtolower($color) === $activeColor) {
                            $activeColorLabel = $color;
                            break;
                        }
                    }
                    if ($activeColorLabel === null) {
                        $activeColorLabel = ucfirst($activeColor);
                    }
                }

                $matchedImageName = null;
                $activeColorToken = $activeColor ? preg_replace('/[^a-z0-9]+/i', '', $activeColor) : '';
                if ($activeColor !== null && !empty($product['product_images'])) {
                    foreach ($product['product_images'] as $img) {
                        if (!empty($img['color']) && strtolower((string) $img['color']) === $activeColor) {
                            $matchedImageName = $img['image'] ?? null;
                            break;
                        }
                    }
                }

                if ($matchedImageName === null && $activeColorToken !== '') {
                    $imageCandidates = [];
                    if (!empty($product['main_image'])) {
                        $imageCandidates[] = $product['main_image'];
                    }
                    if (!empty($product['product_images'])) {
                        foreach ($product['product_images'] as $img) {
                            if (!empty($img['image'])) {
                                $imageCandidates[] = $img['image'];
                            }
                        }
                    }

                    foreach ($imageCandidates as $candidate) {
                        $candidateToken = preg_replace('/[^a-z0-9]+/i', '', strtolower((string) $candidate));
                        if ($candidateToken !== '' && str_contains($candidateToken, $activeColorToken)) {
                            $matchedImageName = $candidate;
                            break;
                        }
                    }
                }

                if (!empty($matchedImageName)) {
                    $image = asset('product-image/medium/'.$matchedImageName);
                } elseif (!empty($product['main_image'])) {
                    $image = asset('product-image/medium/'.$product['main_image']);
                } elseif (!empty($product['product_images'][0]['image'])) {
                    $image = asset('product-image/medium/'.$product['product_images'][0]['image']);
                } else {
                    $image = $fallbackImage;
                }
            @endphp
        <div class="col-lg-4 col-md-6 col-12 pb-4 product-grid-col">
            <div class="card product-item border-0 mb-0 product-grid-card w-100">
                <div class="card-header product-img product-grid-media position-relative overflow-hidden bg-transparent border p-0">
                    <a href="#"><img class="img-fluid w-100 product-grid-image" src="{{ $image }}" alt="{{ $product['product_name'] }}"></a>
                </div>
                <div class="card-body border-left border-right text-center p-0 pt-4 pb-3 product-grid-body">
                    <h6 class="text-truncate mb-3 product-title">{{ $product['product_name'] }}</h6>
                    @if(!empty($activeColorLabel) && !empty($selectedColors))
                        <div class="small text-muted mb-2">
                            Color: {{ $activeColorLabel }}
                        </div>
                    @endif
                    <div class="d-flex justify-content-center align-items-center product-price-wrap">
                        <h6 class="mb-0 product-price">KSH {{ number_format($product['final_price']) }}</h6>
                        @if($product['product_discount'] > 0)
                            <h6 class="text-muted ml-2 mb-0 product-old-price"><del>KSH {{ number_format($product['product_price']) }}</del></h6>
                        @endif
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between bg-light border product-grid-footer">
                    <a href="#" class="btn btn-sm p-0 product-view-link">
                        <i class="fas fa-eye mr-1 product-card-icon"></i>View Detail
                    </a>
                    <a href="javascript:void(0)" class="btn btn-sm btn-primary p-0 addToCartBtn product-add-cart-btn" data-id="{{ $product['id'] }}">
                        <i class="fas fa-shopping-cart mr-1 product-card-icon"></i>Add To Cart
                    </a>
                </div>
            </div>
        </div>
        @endforeach
        <div class = "col-12 pb-1">
            {{ $categoryProducts->appends(request()->except(['json', 'page']))->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
<!-- Shop Product End -->
