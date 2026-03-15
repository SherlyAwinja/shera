@extends('front.layout.layout')
@section('content')

<div class="home-page">

<!-- Carousel Start -->
<div id="header-carousel" class="carousel slide home-hero" data-ride="carousel">
    <div class="carousel-inner">
        @foreach($homeSliderBanners as $key => $sliderBanner)
        <div class="carousel-item hero-slide @if($key == 0) active @endif">
            <a href="{{ $sliderBanner['link'] }}" title="{{ $sliderBanner['title'] }}">
            <img class="img-fluid hero-image" src="{{ asset('front/images/banners/'.$sliderBanner['image']) }}" alt="{{ $sliderBanner['alt'] }}" title="{{ $sliderBanner['title'] }}">
            </a>
        </div>
        @endforeach
    </div>
    @if(count($homeSliderBanners) > 1)
    <a class="carousel-control-prev" href="#header-carousel" data-slide="prev">
        <div class="btn btn-dark hero-control">
            <span class="carousel-control-prev-icon mb-n2"></span>
        </div>
    </a>
    <a class="carousel-control-next" href="#header-carousel" data-slide="next">
        <div class="btn btn-dark hero-control">
            <span class="carousel-control-next-icon mb-n2"></span>
        </div>
    </a>
    @endif
</div>
<!-- Carousel End -->

<!-- Featured Start -->
<div class="container-fluid home-features home-section">
    <div class="row px-xl-5 pb-3">
        <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
            <div class="d-flex align-items-center border mb-4 feature-card">
                <h1 class="fa fa-check text-primary m-0 mr-3 feature-icon"></h1>
                <h5 class="font-weight-semi-bold m-0">Quality Product</h5>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
            <div class="d-flex align-items-center border mb-4 feature-card">
                <h1 class="fa fa-shipping-fast text-primary m-0 mr-2 feature-icon"></h1>
                <h5 class="font-weight-semi-bold m-0">Free Shipping</h5>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
            <div class="d-flex align-items-center border mb-4 feature-card">
                <h1 class="fas fa-exchange-alt text-primary m-0 mr-3 feature-icon"></h1>
                <h5 class="font-weight-semi-bold m-0">14-Day Return</h5>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
            <div class="d-flex align-items-center border mb-4 feature-card">
                <h1 class="fa fa-phone-volume text-primary m-0 mr-3 feature-icon"></h1>
                <h5 class="font-weight-semi-bold m-0">24/7 Support</h5>
            </div>
        </div>
    </div>
</div>
<!-- Featured End -->

@if(count($categories) > 0)
<!-- Categories Start -->
<div class="container-fluid home-categories home-section">
    <div class="row px-xl-5 pb-3">
        @foreach($categories as $category)
            @php
                $image=!empty($category['image']) ? asset('front/images/categories/'.$category['image']) : asset('front/images/categories/no-image.jpg');
            @endphp
            <div class="col-6 col-md-4 col-lg-3 pb-4 d-flex">
                <div class="cat-item d-flex flex-column border mb-0 category-card w-100">
                    <a href="{{ url('category/'.$category['url']) }}" class="cat-img position-relative overflow-hidden mb-3">
                        <img class="img-fluid" src="{{ $image }}" alt="{{ $category['name'] }}">
                    </a>
                    <h5 class="font-weight-semi-bold m-0 category-name">{{ $category['name'] }}</h5>
                </div>
            </div>
        @endforeach
    </div>
</div>
<!-- Categories End -->
@endif

@if(count($homeFixBanners) > 0)
<!-- Offer Start -->
<div class="container-fluid offer home-offers home-section">
    <div class="row px-xl-5">
        @foreach($homeFixBanners as $fixBanner)
        <div class="col-md-6 pb-4">
            <div class="position-relative bg-secondary text-center text-md-right text-white mb-2 py-5 px-5 offer-card" style="background-image: url({{ asset('front/images/banners/' . $fixBanner['image']) }}); background-size: cover;">
            <div class="offer-overlay"></div>
            <div class="position-relative offer-content">
                <h5 class="text-uppercase text-primary mb-3">{{ $fixBanner['title'] }}</h5>
                <h1 class="mb-4 font-weight-semi-bold">{{ $fixBanner['alt'] }}</h1>
                <a href="{{ $fixBanner['link'] }}" class="btn btn-outline-primary py-md-2 px-md-3">Shop Now
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
</div>
<!-- Offer End -->
@endif


@if(count($featuredProducts) > 0)
<!-- Products Start -->
 <div class="container-fluid home-featured-products home-section">
    <div class="text-center mb-4">
        <h2 class="section-title px-5 home-section-title"><span class="px-2">Featured Products</span></h2>

    </div>
    <div class="row px-xl-5 pb-3">
        @foreach($featuredProducts as $product)
            @php
                $fallbackImage = asset('front/images/products/no-image.jpg');
                $image = !empty($product['main_image'])
                    ? asset('front/images/products/'.$product['main_image'])
                    : (!empty($product['product_images'][0]['image'])
                        ? asset('front/images/products/'.$product['product_images'][0]['image'])
                        : $fallbackImage);
        @endphp
        <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
            <div class="card product-item border-0 mb-4 product-card-sleek">
                <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                    <a href="#"><img class="img-fluid w-100" src="{{ $image }}" alt="{{ $product['product_name'] }}"></a>
                </div>
                <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                    <h6 class="text-truncate mb-3">{{ $product['product_name'] }}</h6>
                    <div class="d-flex justify-content-center">
                        <h6 class="product-price-current">KSH{{ $product['final_price'] }}</h6>
                        @if($product['product_discount'] > 0)
                        <h6 class="text-muted ml-2 product-price-old"><del>KSH{{ $product['product_price'] }}</del></h6>
                        @endif
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between bg-light border">
                    <a href="#" class="btn btn-sm text-dark p-0">
                        <i class="fas fa-eye text-primary mr-1"></i>View Detail
                    </a>
                    <a href="javascript:void(0)" class="btn btn-sm text-dark p-0 addToCartBtn product-add-cart" data-id="{{ $product['id'] }}">
                        <i class="fas fa-shopping-cart text-primary mr-1"></i>Add To Cart
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
<!-- Products End -->
@endif

<!-- Subscribe Start -->
<div class="container-fluid bg-secondary home-newsletter home-section">
    <div class="row justify-content-md-center py-5 px-xl-5">
        <div class="col-md-6 col-12 py-5">
            <div class="text-center mb-2 pb-2 newsletter-panel">
                <h2 class="section-title px-5 mb-3 home-section-title"><span class="bg-secondary px-2">Stay Updated</span></h2>
                <p>Stay updated with the latest products, offers & exclusive deals!</p>
            </div>
            <form action="">
                <div class="input-group">
                    <input type="text" class="form-control border-white p-4" placeholder="Enter your email">
                    <div class="input-group-append">
                        <button class="btn btn-primary px-4">Subscribe</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Subscribe End -->


@if(count($newArrivalProducts) > 0)
<!-- Products Start -->
<div class="container-fluid home-new-arrivals home-section">
    <div class="text-center mb-4">
        <h2 class="section-title px-5 home-section-title"><span class="px-2">New Arrivals</span></h2>
    </div>
    <div class="row px-xl-5 pb-3">
        <div class="col-12">
            <div class="owl-carousel new-arrivals-carousel">
        @foreach($newArrivalProducts as $product)
            @php
                $fallbackImage = asset('front/images/products/no-image.jpg');
                $image = !empty($product['main_image'])
                    ? asset('product-image/medium/'.$product['main_image'])
                    : (!empty($product['product_images'][0]['image'])
                        ? asset('product-image/medium/'.$product['product_images'][0]['image'])
                        : $fallbackImage);
        @endphp
        <div class="new-arrival-item">
            <div class="card product-item border-0 mb-0 product-card-sleek new-arrival-card">
                <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                    <a href="#"><img class="img-fluid w-100" src="{{ $image }}" alt="{{ $product['product_name'] }}"></a>
                </div>
                <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                    <h6 class="text-truncate mb-3">{{ $product['product_name'] }}</h6>
                    <div class="d-flex justify-content-center">
                        <h6 class="product-price-current">KSH{{$product['final_price']}}</h6>
                        @if($product['product_discount'] > 0)
                            <h6 class="text-muted ml-2 product-price-old"><del>KSH{{$product['product_price']}}</del></h6>
                        @endif
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between bg-light border">
                    <a href="" class="btn btn-sm text-dark p-0"><i class="fas fa-eye text-primary mr-1"></i>View Detail</a>
                    <a href="" class="btn btn-sm text-dark p-0 product-add-cart"><i class="fas fa-shopping-cart text-primary mr-1"></i>Add To Cart</a>
                </div>
            </div>
        </div>
        @endforeach
            </div>
        </div>
    </div>
</div>
<!-- Products End -->
 @endif


@php
    $vendorType = null;
    $vendorLogos = [];
    if (!empty($brandLogos) && count($brandLogos) > 0) {
        $vendorType = 'brand';
        $vendorLogos = $brandLogos;
    } elseif (!empty($logoBanners) && count($logoBanners) > 0) {
        $vendorType = 'banner';
        $vendorLogos = $logoBanners;
    }
@endphp

@if(count($vendorLogos) > 0)
<!-- Vendor Start -->
<div class="container-fluid home-vendors home-section">
    <div class="row px-xl-5">
        <div class="col">
            <div class="owl-carousel vendor-carousel">
                @foreach($vendorLogos as $logo)
                    @php
                        if ($vendorType === 'brand') {
                            $logoPath = !empty($logo['logo'])
                                ? asset('front/images/logos/'.$logo['logo'])
                                : (!empty($logo['image'])
                                    ? asset('front/images/brands/'.$logo['image'])
                                    : asset('front/images/products/no-image.jpg'));
                            $logoAlt = $logo['name'] ?? 'brand';
                        } else {
                            $logoPath = !empty($logo['image'])
                                ? asset('front/images/banners/'.$logo['image'])
                                : asset('front/images/products/no-image.jpg');
                            $logoAlt = $logo['title'] ?? 'logo';
                        }
                    @endphp
                <div class="vendor-item border p-4 vendor-card">
                    <img src="{{ $logoPath }}" alt="{{ $logoAlt }}">
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<!-- Vendor End -->
@endif

</div>

@endsection
