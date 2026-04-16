@extends('front.layout.layout')
@section('content')
@php
    $activeVariant = !empty($product->color_variants)
        ? $product->color_variants->firstWhere('is_current', true)
        : null;
@endphp

<div class="front-luxe-page detail-page">
<div class="container-fluid bg-secondary mb-5 front-page-hero detail-page-hero">
    <div class="front-page-hero-inner d-flex flex-column align-items-center justify-content-center text-center">
        <span class="front-page-eyebrow">Product Detail</span>
        <h1 class="front-page-title mb-3">{{ $product->product_name }}</h1>
        <p class="front-page-subtitle mb-3">A polished look at materials, color options, and sizing before checkout.</p>
        <div class="front-page-breadcrumb d-inline-flex flex-wrap justify-content-center">
            <p class="m-0"><a href="{{ url('/') }}">Home</a></p>

            @if($product->category)
                @if($product->category->parentcategory)
                    <p class="m-0 px-2">/</p>
                    <p class="m-0">
                        <a href="/{{ $product->category->parentcategory->url }}">
                            {{ $product->category->parentcategory->name }}
                        </a>
                    </p>
                @endif

                <p class="m-0 px-2">/</p>
                <p class="m-0">
                    <a href="/{{ $product->category->url }}">
                        {{ $product->category->name }}
                    </a>
                </p>
            @endif

            <p class="m-0 px-2">/</p>
            <p class="m-0">{{ $product->product_name }}</p>
        </div>
    </div>
</div>
<div class="container-fluid front-page-shell pb-5">
    <div class="row px-xl-5">
        <div class="col-lg-5 pb-5">
            <div class="detail-media-card">
                <div id="product-carousel" class="carousel slide detail-carousel" data-ride="carousel">
                    <div class="carousel-inner border-0">
                        <div class="carousel-item active">
                            <img
                                class="w-100 h-100 zoom-image product-main-image detail-carousel-image"
                                src="{{ $variantState['image'] }}"
                                data-zoom-image="{{ $variantState['image'] }}"
                                alt="{{ $product->product_name }}"
                            >
                            @php
                            $image = [];
                            if ($product->product_images) {
                                $images[] = $product->product_image;
                            }
                            foreach($product->product_images as $img) {
                                $images[] = $img->image;
                            }

                            // Approved reviews count and average
                            $approvedReviewsQuery = $product->reviews()->where('status', '1');
                            $approvedCount = $approvedReviewsQuery->count();
                            $averageRating = $approvedCount > 0 ? round($approvedReviewsQuery->avg('rating'), 1) : 0;
                            @endphp
                        </div>

                        @foreach($product->product_images as $image)
                            <div class="carousel-item">
                                <img
                                    class="w-100 h-100 zoom-image detail-carousel-image"
                                    src="{{ asset('front/images/products/' . $image->image) }}"
                                    data-zoom-image="{{ asset('front/images/products/' . $image->image) }}"
                                    alt="{{ $product->product_name }}"
                                >
                            </div>
                        @endforeach

                        @if(!empty($product->product_video))
                            <div class="carousel-item">
                                <div class="detail-video-frame">
                                    <video class="detail-product-video" controls>
                                        <source src="{{ asset('front/videos/products/' . $product->product_video) }}" type="video/mp4">
                                        Your browser does not support the video tag.
                                    </video>
                                </div>
                            </div>
                        @endif
                    </div>

                    <a class="carousel-control-prev detail-carousel-control" href="#product-carousel" data-slide="prev">
                        <i class="fa fa-2x fa-angle-left"></i>
                    </a>

                    <a class="carousel-control-next detail-carousel-control" href="#product-carousel" data-slide="next">
                        <i class="fa fa-2x fa-angle-right"></i>
                    </a>
                </div>

                <div class="detail-media-footer">
                    <div class="detail-feature-chip">
                        <i class="fa fa-search-plus"></i>
                        <span>Zoom enabled</span>
                    </div>
                    <div class="detail-feature-chip">
                        <i class="fa fa-images"></i>
                        <span>Multi-angle gallery</span>
                    </div>
                    @if(!empty($product->product_video))
                        <div class="detail-feature-chip">
                            <i class="fa fa-play-circle"></i>
                            <span>Video preview</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7 pb-5">
            <div class="detail-summary-card">
            <form id="addToCart" action="{{ route('cart.store') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" id="selected-product-id" value="{{ $variantState['product_id'] }}">
                <input type="hidden" name="color" id="selected-color-input" value="{{ $variantState['color'] ?: optional($activeVariant)->color_display }}">
                <input type="hidden" name="replace_qty" value="1">

                <div class="detail-summary-head">
                    <span class="detail-kicker">Shera Signature</span>
                    <h2 class="font-weight-semi-bold detail-product-title">{{ $product->product_name }}</h2>

                    <div class="d-flex align-items-center flex-wrap mb-3 detail-review-row">
                        <div class = "product-stars text-primary mr-2">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= floor($averageRating))
                                    <small class="fas fa-star"></small>
                                @elseif($i == ceil($averageRating) && $averageRating - floor($averageRating) >= 0.5)
                                    <small class="fas fa-star-half-alt"></small>
                                @else
                                    <small class="far fa-star"></small>
                                @endif
                            @endfor
                        </div>
                        <small class="pt-1">({{ $approvedCount }} Reviews)</small>
                    </div>

                    <div class="detail-meta-strip">
                        @if(!empty($product->product_code))
                            <span class="detail-meta-pill">Code: {{ $product->product_code }}</span>
                        @endif
                        @if($product->category)
                            <span class="detail-meta-pill">{{ $product->category->name }}</span>
                        @endif
                        <span class="detail-meta-pill">{{ $variantState['color'] ?: optional($activeVariant)->color_display ?: 'Classic finish' }}</span>
                    </div>
                </div>

                <div class="detail-price-wrap getAttributePrice" id="product-price-display">
                    @if($variantState['has_discount'])
                        <span class="text-danger final-price">KSH.{{ number_format($variantState['final_price']) }}</span>
                        <del class="text-muted original-price">KSH.{{ number_format($variantState['product_price']) }}</del>
                    @else
                        <span class="final-price">KSH.{{ number_format($variantState['product_price']) }}</span>
                    @endif
                </div>

                @if(!empty($product->description))
                    <div class="detail-copy mb-4">{!! $product->description !!}</div>
                @endif

                <div id="product-size-section" class="detail-option-block {{ $variantState['has_sizes'] ? '' : 'd-none' }}">
                    <div class="d-flex flex-wrap align-items-center detail-option-row">
                        <p class="text-dark font-weight-medium mb-0 mr-3 detail-option-label">Sizes</p>

                        <div id="product-size-options" class="d-flex flex-wrap align-items-center detail-option-group">
                        @foreach($variantState['sizes'] as $sizeOption)
                            <div class="custom-control custom-radio custom-control-inline detail-size-option">
                                <input
                                    type="radio"
                                    class="custom-control-input getPrice"
                                    id="size-{{ $sizeOption['id'] }}"
                                    name="size"
                                    value="{{ $sizeOption['size'] }}"
                                    data-product-id="{{ $variantState['product_id'] }}"
                                    {{ $sizeOption['checked'] ? 'checked' : '' }}
                                    {{ $sizeOption['in_stock'] ? '' : 'disabled' }}
                                >
                                <label class="custom-control-label" for="size-{{ $sizeOption['id'] }}">
                                    {{ $sizeOption['size'] }}{{ $sizeOption['in_stock'] ? '' : ' (Out of stock)' }}
                                </label>
                            </div>
                        @endforeach
                        </div>
                    </div>
                </div>

                <p id="product-size-empty" class="detail-empty-copy mb-4 {{ $variantState['has_sizes'] ? 'd-none' : '' }}">
                    This color is sold without selectable sizes.
                </p>

                @if(!empty($product->color_variants) && $product->color_variants->count() > 0)
                    <div class="detail-option-block">
                        <div class="d-flex flex-wrap align-items-center detail-option-row">
                            <p class="text-dark font-weight-medium mb-0 mr-3 detail-option-label">Colors</p>

                            <div class="d-flex flex-wrap align-items-center detail-color-group">
                                @foreach($product->color_variants as $gp)
                                    <button
                                        type="button"
                                        class="color-swatch js-color-swatch {{ $gp->is_current ? 'active' : '' }}"
                                        style="background: {{ $gp->swatch_background }}"
                                        title="{{ $gp->color_display }}"
                                        aria-label="{{ $gp->color_display }}"
                                        aria-pressed="{{ $gp->is_current ? 'true' : 'false' }}"
                                        data-color="{{ $gp->color_display }}"
                                        data-image="{{ $gp->image_url }}"
                                        data-product-id="{{ $gp->id }}">
                                    </button>
                                @endforeach

                                <span class="color-swatch-label" id="selected-color-label">
                                    {{ $variantState['color'] ?: optional($activeVariant)->color_display }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="detail-option-block detail-stock-row">
                    <p class="text-dark font-weight-medium mb-0 mr-3 detail-option-label">Availability</p>
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="badge variant-stock-badge {{ $variantState['in_stock'] ? 'badge-success' : 'badge-danger' }}">
                            {{ $variantState['stock_label'] }}
                        </span>
                        <small class="variant-stock-text">{{ $variantState['stock_message'] }}</small>
                    </div>
                </div>

                <div class="detail-purchase-row">
                    <div class="input-group quantity detail-qty-group">
                        <div class="input-group-btn">
                            <button type="button" class="btn btn-primary btn-minus detail-qty-control" {{ $variantState['can_purchase'] ? '' : 'disabled' }}>
                                <i class="fa fa-minus"></i>
                            </button>
                        </div>

                        <input
                            type="number"
                            name="qty"
                            class="form-control bg-secondary text-center detail-qty-input"
                            value="1"
                            min="1"
                            inputmode="numeric"
                            {{ $variantState['can_purchase'] ? '' : 'disabled' }}
                        >

                        <div class="input-group-btn">
                            <button type="button" class="btn btn-primary btn-plus detail-qty-control" {{ $variantState['can_purchase'] ? '' : 'disabled' }}>
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary px-4 addToCartBtn detail-add-to-cart" {{ $variantState['can_purchase'] ? '' : 'disabled' }}>
                        <i class="fa fa-shopping-cart mr-1"></i> Add To Cart
                    </button>
                </div>

                <p id="purchase-status-message" class="detail-status-message {{ $variantState['can_purchase'] ? 'd-none' : '' }}">
                    {{ $variantState['stock_message'] }}
                </p>

                <div class="print-success-msg detail-inline-message mt-3" style="display:none;"></div>
                <div class="print-error-msg detail-inline-message mt-3" style="display:none;"></div>

            </form>

            <div class="detail-assurance-grid">
                <div class="detail-assurance-item">
                    <i class="fa fa-shield-alt"></i>
                    <span>Secure checkout flow</span>
                </div>
                <div class="detail-assurance-item">
                    <i class="fa fa-exchange-alt"></i>
                    <span>Easy size and color review</span>
                </div>
                <div class="detail-assurance-item">
                    <i class="fa fa-headset"></i>
                    <span>Support when you need it</span>
                </div>
            </div>

            <div class="d-flex align-items-center flex-wrap pt-2 detail-share-row">
                <p class="text-dark font-weight-medium mb-0 mr-3">Share</p>

                <div class="d-inline-flex align-items-center flex-wrap detail-share-links">
                    <a class="text-dark" href="#"><i class="fab fa-facebook-f"></i></a>
                    <a class="text-dark" href="#"><i class="fab fa-twitter"></i></a>
                    <a class="text-dark" href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a class="text-dark" href="#"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            </div>

        </div>

    </div>
    <div class="row px-xl-5">
        <div class="col">
            <div class="detail-info-card">
                <div class="nav nav-tabs justify-content-center border-secondary mb-4 detail-tabs">
                    <a class="nav-item nav-link active" data-toggle="tab" href="#tab-pane-1">Description</a>
                    <a class="nav-item nav-link" data-toggle="tab" href="#tab-pane-2">Reviews ({{ $approvedCount }})</a>
                </div>
                <div class="tab-content detail-tab-content">
                    <div class="tab-pane fade show active" id="tab-pane-1">
                        <div class="detail-tab-copy">{!! $product->description !!}</div>
                    </div>
                    <div class="tab-pane fade" id="tab-pane-2">
                        <div class="row">

                            {{-- Reviews List --}}
                            <div class="col-md-6">
                                <h4 class="mb-4">
                                    ({{ $approvedCount }}) review(s) for "{{ $product->product_name }}"
                                </h4>

                                @forelse($product->reviews()->where('status', 1)->latest()->get() as $review)
                                    <div class="media mb-4">

                                        {{-- User Avatar --}}
                                        <img
                                            src="{{ $review->user && $review->user->avatar
                                                    ? asset('storage/' . $review->user->avatar)
                                                    : asset('front/images/default-user.jpg') }}"
                                            alt="Image"
                                            class="img-fluid mr-3 mt-1"
                                            style="width: 45px;"
                                        >

                                        <div class="media-body">
                                            <h6>
                                                {{ $review->user->name ?? 'Guest' }}
                                                <small> - <i>{{ $review->created_at->format('d M Y') }}</i></small>
                                            </h6>

                                            {{-- Rating Stars --}}
                                            <div class="text-primary mb-2">
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($i <= $review->rating)
                                                        <i class="fas fa-star"></i>
                                                    @else
                                                        <i class="far fa-star"></i>
                                                    @endif
                                                @endfor
                                            </div>

                                            <p>{{ $review->review }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <p>No reviews yet. Be the first to review this product.</p>
                                @endforelse
                            </div>

                            {{-- Review Form --}}
                            <div class="col-md-6">
                                <h4 class="mb-4">Leave a review</h4>
                                <small>Your email address will not be published. Required fields are marked *</small>

                                {{-- Flash Messages --}}
                                @if(session('success_message'))
                                    <div class="alert alert-success mt-3">
                                        {{ session('success_message') }}
                                    </div>
                                @endif

                                @if(session('error_message'))
                                    <div class="alert alert-danger mt-3">
                                        {{ session('error_message') }}
                                    </div>
                                @endif

                                @auth
                                    @php
                                        $hasReviewed = $product->reviews()
                                            ->where('user_id', auth()->id())
                                            ->exists();
                                    @endphp

                                    @if($hasReviewed)
                                        <div class="alert alert-info mt-3">
                                            You have already submitted a review for this product. Thank you!
                                        </div>
                                    @else

                                        {{-- Star Rating --}}
                                        <div class="d-flex my-3">
                                            <p class="mb-0 mr-2">Your Rating:</p>
                                            <div id="star-rating" class="text-primary" style="font-size:20px; cursor:pointer;">
                                                <i class="far fa-star" data-value="1"></i>
                                                <i class="far fa-star" data-value="2"></i>
                                                <i class="far fa-star" data-value="3"></i>
                                                <i class="far fa-star" data-value="4"></i>
                                                <i class="far fa-star" data-value="5"></i>
                                            </div>
                                        </div>

                                        {{-- Review Form --}}
                                        <form id="reviewForm" action="{{ route('product.review.store') }}" method="POST">
                                            @csrf

                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <input type="hidden" name="rating" id="ratingInput" value="0">

                                            <div class="form-group">
                                                <label for="message">Your Review *</label>
                                                <textarea
                                                    id="message"
                                                    name="review"
                                                    cols="30"
                                                    rows="5"
                                                    class="form-control"
                                                    required
                                                ></textarea>
                                            </div>

                                            <div class="form-group">
                                                <label>Your Name *</label>
                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    value="{{ auth()->user()->name }}"
                                                    readonly
                                                >
                                            </div>

                                            <div class="form-group">
                                                <label>Your Email *</label>
                                                <input
                                                    type="email"
                                                    class="form-control"
                                                    value="{{ auth()->user()->email }}"
                                                    readonly
                                                >
                                            </div>

                                            <div class="form-group mb-0">
                                                <input
                                                    type="submit"
                                                    value="Leave Your Review"
                                                    class="btn btn-primary px-3"
                                                >
                                            </div>
                                        </form>
                                    @endif

                                @else
                                    <div class="alert alert-warning mt-3">
                                        Please <a href="{{ route('user.login') }}">login</a> to submit a review.
                                    </div>
                                @endauth
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Shop Detail End -->

@if($product->similar_products->isNotEmpty())
<!-- Similar Products Start -->
<div class="container-fluid pb-5">
    <div class="text-center mb-4">
        <h2 class="section-title px-5 detail-section-title">
            <span class="px-2">You May Also Like</span>
        </h2>
    </div>

    <div class="row px-xl-5 detail-similar-grid">
        @foreach($product->similar_products as $similar)
            @php
                $fallbackImage = asset('front/images/products/no-image.jpg');
                $image = '';

                if (!empty($similar->main_image)) {
                    $image = asset('front/images/products/' . $similar->main_image);
                } elseif ($similar->product_images && $similar->product_images->first()) {
                    $image = asset('front/images/products/' . $similar->product_images->first()->image);
                } else {
                    $image = $fallbackImage;
                }
            @endphp

            <div class="col-lg-4 col-md-6 col-sm-12 pb-1 detail-similar-col">
                <div class="card product-item border-0 mb-4 detail-similar-card">

                    <!-- Product Image -->
                    <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0 detail-similar-media">
                        <a href="/{{ $similar->product_url }}">
                            <img class="img-fluid w-100" src="{{ $image }}" alt="{{ $similar->product_name }}">
                        </a>
                    </div>

                    <!-- Product Details -->
                    <div class="card-body border-left border-right text-center p-0 pt-4 pb-3 detail-similar-body">
                        <h6 class="text-truncate mb-3 detail-similar-title">{{ $similar->product_name }}</h6>
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <h6 class="detail-similar-price mb-0">KSH.{{ number_format($similar->final_price ?? $similar->product_price) }}</h6>

                            @if(!empty($similar->product_discount) && $similar->product_discount > 0)
                                <h6 class="text-muted ml-2 mb-0 detail-similar-old-price">
                                    <del>KSH.{{ number_format($similar->product_price) }}</del>
                                </h6>
                            @endif
                        </div>

                        @if(!$similar->is_available)
                            <small class="text-muted d-block">Currently out of stock</small>
                        @elseif(!$similar->can_quick_add)
                            <small class="text-muted d-block">Choose options on the detail page</small>
                        @elseif($similar->has_selectable_sizes && $similar->can_quick_add)
                            <small class="text-muted d-block">Quick add size: {{ $similar->quick_add_size }}</small>
                        @endif
                    </div>

                    <!-- Product Actions -->
                    <div class="card-footer d-flex justify-content-between bg-light border detail-similar-footer">
                        <a href="/{{ $similar->product_url }}" class="btn btn-sm text-dark p-0 detail-similar-link">
                            <i class="fas fa-eye text-primary mr-1"></i>View Detail
                        </a>

                        @if($similar->can_quick_add)
                            <a href="javascript:void(0);"
                               class="btn btn-sm text-dark p-0 addToCartBtn detail-similar-cart"
                               data-id="{{ $similar->id }}"
                               data-size="{{ $similar->quick_add_size }}"
                               data-color="{{ $similar->quick_add_color }}">
                                <i class="fas fa-shopping-cart text-primary mr-1"></i>Add To Cart
                            </a>
                        @elseif($similar->is_available)
                            <a href="/{{ $similar->product_url }}" class="btn btn-sm text-dark p-0 detail-similar-cart">
                                <i class="fas fa-ruler-combined text-primary mr-1"></i>Choose Options
                            </a>
                        @else
                            <span class="btn btn-sm text-muted p-0 detail-similar-cart disabled" aria-disabled="true">
                                <i class="fas fa-ban mr-1"></i>Out of Stock
                            </span>
                        @endif
                    </div>

                </div>
            </div>
        @endforeach
    </div>
</div>
<!-- Similar Products End -->
@endif
</div>

@endsection
