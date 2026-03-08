@extends('front.layout.layout')
@section('content')

<div class="product-list-page">
    <!-- Page Header Start -->
    <div class="container-fluid bg-secondary mb-5">
        <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 150px">
            <h1 class="font-weight-semi-bold text-uppercase mb-3">{{ $categoryDetails['name'] }}</h1>
            <div class="d-inline-flex">
                <p class="m-0"><a href="{{ url('/') }}">Home</a></p>
                <p class="m-0 px-2">-</p>
                <p class="m-0">{{ $categoryDetails['name'] }}</p>
            </div>
        </div>
    </div>
    <!-- Page Header End -->

    <!-- Shop Start -->
    <div class="container-fluid pt-5">
        <div class="row px-xl-5 shop-results-grid" id="appendProducts">
            @include('front.products.ajax_products_listing')
        </div>
    </div>
    <!-- Shop End -->
</div>

@endsection
