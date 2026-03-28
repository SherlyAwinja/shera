<?php
Use App\Models\Category;
// Get Categories and their sub categories
$categories = Category::getCategories('Front');
/*echo "<pre>"; print_r($categories);die;*/
$totalCartItems = totalCartItems();
?>

<header class="front-header {{ request()->path() === '/' || isset($categoryDetails) ? 'home-header-theme' : '' }} {{ isset($categoryDetails) ? 'listing-header-teal' : '' }}">
<!-- Topbar Start -->
<div class="container-fluid">
    <div class="row bg-secondary py-2 px-xl-5 front-topbar">
        <div class="col-lg-6 d-none d-lg-flex align-items-center">
            <div class="d-inline-flex align-items-center topbar-links">
                <a class="text-dark" href="#">FAQs</a>
                <span class="text-muted px-2">|</span>
                <a class="text-dark" href="#">Help</a>
                <span class="text-muted px-2">|</span>
                <a class="text-dark" href="#">Support</a>
            </div>
        </div>
        <div class="col-lg-6 col-12 text-center text-lg-right">
            <div class="d-inline-flex align-items-center topbar-social">
                <a class="text-dark px-2" href="">
                <i class="fab fa-facebook-f"></i>
                </a>
                <a class="text-dark px-2" href="">
                <i class="fab fa-twitter"></i>
                </a>
                <a class="text-dark px-2" href="">
                <i class="fab fa-linkedin-in"></i>
                </a>
                <a class="text-dark px-2" href="">
                <i class="fab fa-instagram"></i>
                </a>
                <a class="text-dark pl-2" href="">
                <i class="fab fa-youtube"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="row align-items-center py-3 px-xl-5 front-brandbar">
        <div class="col-lg-3 d-none d-lg-block">
            <a href="/" class="text-decoration-none header-logo-link" aria-label="SHERA Home">
                <h1 class="m-0 header-logo">
                    <span class="shera-logo">
                        <span class="shera-logo-mark" aria-hidden="true">S</span>
                        <span class="shera-logo-word">SHERA</span>
                    </span>
                </h1>
            </a>
        </div>
        <div class="col-lg-6 col-6 text-left">
            <!-- Search Form with live search container -->
            <div class="search-wrapper" style="position: relative; width: 100%;">
                <form action="javascript:void(0);">
                    <div class="input-group">
                        <input
                            type="text"
                            class="form-control"
                            id="search_input"
                            name="q"
                            placeholder="Search for products"
                            autocomplete="off"
                            aria-label="Search for products"
                            aria-controls="search_result"
                        >
                        <div class="input-group-append">
                            <span class="input-group-text bg-transparent text-primary">
                                <i class="fa fa-search"></i>
                            </span>
                        </div>
                    </div>
                </form>

                <!-- Live search results -->
                <div
                    id="search_result"
                    style="display:none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ddd; border-top: none; z-index: 999;"
                    aria-live="polite"
                >
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6 text-right">
            <div class="header-actions">
                <a href="" class="btn border header-action-btn">
                <i class="fas fa-heart text-primary"></i>
                <span class="badge">0</span>
                </a>
                <!-- Cart Link -->
                <a href="{{ route('cart.index') }}" class="btn border">
                    <i class="fas fa-shopping-cart text-primary"></i>
                    <span class="badge cart-count totalCartItems">{{ $totalCartItems }}</span>
                </a>
            </div>
        </div>
    </div>
</div>
<!-- Topbar End -->
<!-- Navbar Start -->
<div class="container-fluid mb-2">
    <div class="row border-top px-xl-5 front-navrow">
        <div class="col-12">
            <nav class="navbar navbar-expand-lg bg-light navbar-light py-3 py-lg-0 px-0 front-main-nav">
                <a href="/" class="text-decoration-none d-block d-lg-none header-logo-link" aria-label="SHERA Home">
                    <h1 class="m-0 header-logo">
                        <span class="shera-logo">
                            <span class="shera-logo-mark" aria-hidden="true">S</span>
                            <span class="shera-logo-word">SHERA</span>
                        </span>
                    </h1>
                </a>
                <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                    <div class="navbar-nav mr-auto py-0 main-nav-links">
                        <a href="{{ route('home', [], false) }}" class="nav-item nav-link">Home</a>
                            @foreach($categories as $category)
                                @if($category['menu_status'] == 1)
                                    @if(count($category['subcategories'])>0)
                                    <div class="nav-item dropdown">
                                        <a href="/{{$category['url']}}" class="nav-link dropdown-toggle" data-toggle="dropdown">
                                            {{$category['name']}}
                                        </a>
                                        <div class="dropdown-menu rounded-0 m-0">
                                            @foreach($category['subcategories'] as $subcategory)
                                                @if($subcategory['menu_status']== 1)
                                                    @if(isset($subcategory['subsubcategories']) && count($subcategory['subsubcategories'])>0)
                                                    <div class="dropdown-submenu">
                                                        <a href="/{{$subcategory['url']}}" class="dropdown-item">
                                                            {{$subcategory['name']}}
                                                            <i class="fa fa-angle-right float-right mt-1"></i>
                                                        </a>
                                                        <div class="dropdown-menu">
                                                            @foreach($subcategory['subsubcategories'] as $subsubcategory)
                                                                @if($subsubcategory['menu_status']== 1)
                                                                    <a href="/{{$subsubcategory['url']}}" class="dropdown-item">
                                                                        {{$subsubcategory['name']}}
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    @else
                                                    <a href="/{{$subcategory['url']}}" class="dropdown-item">
                                                        {{$subcategory['name']}}
                                                    </a>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    @else
                                    <a href="/{{$category['url']}}" class="nav-item nav-link">
                                        {{$category['name']}}
                                    </a>
                                    @endif
                                @endif
                            @endforeach
                        <a href="contact.html" class="nav-item nav-link">Contact Us</a>
                    </div>
                    <div class="navbar-nav ml-auto py-0 main-nav-auth">
                        @guest
                            <a href="{{ route('user.login', [], false) }}" class="nav-item nav-link {{ request()->routeIs('user.login') ? 'active' : '' }}">Login</a>
                            <a href="{{ route('user.register', [], false) }}" class="nav-item nav-link {{ request()->routeIs('user.register') ? 'active' : '' }}">Register</a>
                        @else
                            <a href="{{ route('user.account', [], false) }}" class="nav-item nav-link {{ request()->routeIs('user.account') || request()->routeIs('user.account.update') ? 'active' : '' }}">My Account</a>
                            <span class="nav-item nav-link">{{ auth()->user()->name ?: auth()->user()->email }}</span>
                            <form method="POST" action="{{ route('user.logout', [], false) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="nav-item nav-link btn btn-link bg-transparent border-0 p-0">Logout</button>
                            </form>
                        @endguest
                    </div>
                </div>
            </nav>
        </div>
    </div>
</div>
<!-- Navbar End -->
</header>
