@extends('admin.layout.layout')
@section('content')
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Dashboard v2</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Dashboard v2</li>
                    </ol>
                </div>
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    @if(Session::has('success_message'))
    <div class="alert alert-success alert-dismissible fade show" m-3 role="alert">
        <strong>Success!</strong> {{ Session::get('success_message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if(Session::has('error_message'))
    <div class="alert alert-danger alert-dismissible fade show" m-3 role="alert">
        <strong>Error!</strong> {{ Session::get('error_message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!--begin::App Content-->
    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <!--begin::Col-->
                <div class="col-lg-3 col-6">
                    <!--begin::Small Box Widget 1-->
                    <div class="small-box text-bg-primary">
                        <div class="inner">
                            <h3>{{ $categoriesCount }}</h3>
                            <p>Categories</p>
                        </div>
                        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M10 3H4a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1V4a1 1 0 
                            00-1-1zM9 9H5V5h4vzm11-6h-6a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1V4a1 1 0 
                            00-1-1zm-1 6h-4V5h4v4zm-9 4H4a1 1 0 00-1 1v6a1 1 0 001 1h6a1 1 0 001-1v-6a1 1 0 
                            00-1-1zm-1 6H5v-4h4v4zm8-6c-2.206 0-4 1.794-4 4s1.794 4 4 4 4-1.794 4-4-1.794-4-4-4zm0
                            6c-1.103 0-2-.897-2-2s.897-2 2-2 2 .897 2 2-.897 2-2 2z"></path>
                        </svg>
                        <a href="{{ url('admin/categories') }}" class="small-box-footer link-light link-
                        underline-opacity-0 link-underline-opacity-50-hover">
                        More Info <i class="bi bi-link-45deg"></i>
                        </a>
                    </div>
                    <!--end::Small Box Widget 1-->
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-lg-3 col-6">
                    <!--begin::Small Box Widget 2-->
                    <div class="small-box text-bg-success">
                        <div class="inner">
                            <h3>{{ $productsCount }}</h3>
                            <p>Products</p>
                        </div>
                        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M7 5h10a2 2 0 012 2v10a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 
                            012-2zm0 1a1 1 0 00-1 1v10a1 1 0 001 1h10a1 1 0 001-1V7a1 1 0 00-1-1H7zm1 2h8a1 1 0 
                            011 1v8a1 1 0 01-1 1H8a1 1 0 01-1-1V9a1 1 0 011-1z"></path>
                        </svg>
                        <a href="{{ url('admin/products') }}" class="small-box-footer link-light link-
                        underline-opacity-0 link-underline-opacity-50-hover">
                        More Info <i class="bi bi-link-45deg"></i>
                        </a>
                    </div>
                    <!--end::Small Box Widget 2-->
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-lg-3 col-6">
                    <!--begin::Small Box Widget 3-->
                    <div class="small-box text-bg-warning">
                        <div class="inner">
                            <h3>{{ $brandsCount }}</h3>
                            <p>Brands</p>
                        </div>
                        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c.4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 
                            8zm-5-8h10v2H7z"></path>
                        </svg>
                        <a href="{{ url('admin/brands') }}" class="small-box-footer link-dark link-
                        underline-opacity-0 link-underline-opacity-50-hover">
                        More Info <i class="bi bi-link-45deg"></i>
                        </a>
                    </div>
                    <!--end::Small Box Widget 3-->
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-lg-3 col-6">
                    <!--begin::Small Box Widget 4-->
                    <div class="small-box text-bg-danger">
                        <div class="inner">
                            <h3>{{ $usersCount }}</h3>
                            <p>Users</p>
                        </div>
                        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M12 2C6.579 2 2 6.579 2 12s4.579 10 10 10 10-4.579 
                            10-10S17.421 2 12 2zm0 5c1.727 0 3 1.273 3 3s-1.273 3-3 3c-1.726 0-3-1.272-3-3s1.274-3 
                            3-3zm0 12.2c-2.538 0-4.93-1.119-6.541-3.085C5.47 13.701 8.057 13 12 13c3.943 0 
                            6.531.701 7.541 3.115-1.612 1.966-4.004 3.085-6.541 3.085z"></path>
                        </svg>
                        <a href="{{ url('admin/users') }}" class="small-box-footer link-light link-
                        underline-opacity-0 link-underline-opacity-50-hover">
                        More Info <i class="bi bi-link-45deg"></i>
                        </a>
                    </div>
                    <!--end::Small Box Widget 4-->
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row">
                <!--begin::Col-->
                <div class="col-lg-3 col-6">
                    <!--begin::Small Box Widget 1-->
                    <div class="small-box text-bg-secondary">
                        <div class="inner">
                            <h3>{{ $ordersCount }}</h3>
                            <p>Orders</p>
                        </div>
                        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M20 4H4c-1.103 0-2 .897-2 2v12c0 1.103.897 2 2 2h16c1.103 0 
                            2-.897 2-2V6c0-1.103-.897-2-2-2zm0 2v.511l-8 6.223-8-6.222V6h16zM4 18V9.044|7.386 
                            5.745a.994.994 0 001.228 0L20 9.044 20.002 18H4z"></path>
                        </svg>
                        <a href="{{ url('admin/orders') }}" class="small-box-footer link-light link-
                        underline-opacity-0 link-underline-opacity-50-hover">
                        More Info <i class="bi bi-link-45deg"></i>
                        </a>
                    </div>
                    <!--end::Small Box Widget 1-->
                </div>
                <!--end::Col-->
                <!--begin::Col-->
                <div class="col-lg-3 col-6">
                    <!--begin::Small Box Widget 2-->
                    <div class="small-box text-bg-info">
                        <div class="inner">
                            <h3>{{ $couponsCount }}</h3>
                            <p>Coupons</p>
                        </div>
                        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M21 5H3a1 1 0 00-1 1v4h.893c.996 0 1.92.681 2.08 1.664A2.001 
                            01-1.973-2.336c.16-.983 1.084-1.664 2.08-1.664H22V6a1 1 0 00-1-1zM4 8.5a.5.5 0 11-1 
                            0 .5.5 0 011 0zm1.5 5.5a.5.5 0 110-1 .5.5 0 010 1z"></path>
                        </svg>
                        <a href="{{ url('admin/coupons') }}" class="small-box-footer link-light link-
                        underline-opacity-0 link-underline-opacity-50-hover">
                        More Info <i class="bi bi-link-45deg"></i>
                        </a>
                    </div>
                    <!--end::Small Box Widget 2-->
                </div>
                <!--end::Col-->
                <div class="col-lg-3 col-6">
                    <!--begin::Small Box Widget 3-->
                    <div class="small-box text-bg-muted">
                        <div class="inner">
                            <h3>{{ $pagesCount }}</h3>
                            <p>Pages</p>
                        </div>
                        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M19 2H5a2 2 0 00-2 2v16a2 2 0 002 2h14a2 2 0 002-2V4a2 2 0 
                            002-2-2zm0 18H5V4h14v16zM7 8h10v2H7z"></path>
                        </svg>
                        <a href="{{ url('admin/pages') }}" class="small-box-footer link-dark link-
                        underline-opacity-0 link-underline-opacity-50-hover">
                        More Info <i class="bi bi-link-45deg"></i>
                        </a>
                    </div>
                    <!--end::Small Box Widget 3-->
                    </div>
                    <!--end::Col-->
                    <div class="col-lg-3 col-6">
                        <!--begin::Small Box Widget 4-->
                        <div class="small-box text-bg-light">
                            <div class="inner">
                                <h3>{{ $enquiriesCount }}</h3>
                                <p>Enquiries</p>
                            </div>
                            <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M20 4H4c-1.103 0-2 .897-2 2v12c0 1.103.897 2 2 h16c1.103 0 
                                2-.897 2-2V6c0-1.103-.897-2-2-2zm0 2v.511|-8 6.223-8-6.222V6h16zM4 18V9.044|7.383 
                                5.745a.994.994 0 001.228 0L20 9.044 20.002 18H4z"></path>
                            </svg>
                            <a href="{{ url('admin/enquiries') }}" class="small-box-footer link-dark link-
                            underline-opacity-0 link-underline-opacity-50-hover">
                            More Info <i class="bi bi-link-45deg"></i>
                            </a>
                        </div>
                        <!--end::Small Box Widget 4-->
                    </div>
                    <!--end::Col-->
                </div>
            </div>
        </div>
    </div>
</main>
@endsection