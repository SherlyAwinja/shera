@extends('admin.layout.layout')

@section('content')
@php
    $roleLabel = $isAdmin ? 'Administrator' : 'Subadmin';
    $dashboardTitle = $isAdmin ? 'Dashboard' : 'Operations Dashboard';
    $dashboardSubtitle = $isAdmin
        ? 'Track platform health, moderation workload, and store growth from one control surface.'
        : 'Stay on top of the modules assigned to you and move quickly through daily operations.';

    $canView = function (array $modules = [], bool $always = false) use ($dashboardPermissions) {
        if ($always || empty($modules)) {
            return true;
        }

        foreach ($modules as $module) {
            if (!empty($dashboardPermissions[$module])) {
                return true;
            }
        }

        return false;
    };

    $primaryStats = [
        [
            'label' => 'Categories',
            'value' => $categoriesCount,
            'meta' => 'Store structure',
            'icon' => 'bi-diagram-3',
            'tone' => 'ocean',
            'url' => url('admin/categories'),
            'modules' => ['categories'],
        ],
        [
            'label' => 'Products',
            'value' => $productsCount,
            'meta' => 'Inventory records',
            'icon' => 'bi-bag-check',
            'tone' => 'teal',
            'url' => url('admin/products'),
            'modules' => ['products'],
        ],
        [
            'label' => 'Brands',
            'value' => $brandsCount,
            'meta' => 'Vendor identities',
            'icon' => 'bi-bookmark-star',
            'tone' => 'gold',
            'url' => url('admin/brands'),
            'modules' => ['brands'],
        ],
        [
            'label' => 'Users',
            'value' => $usersCount,
            'meta' => 'Customer accounts',
            'icon' => 'bi-people',
            'tone' => 'berry',
            'url' => url('admin/users'),
            'modules' => ['users'],
        ],
        [
            'label' => 'Reviews',
            'value' => $reviewsCount,
            'meta' => 'Published and pending',
            'icon' => 'bi-chat-square-heart',
            'tone' => 'slate',
            'url' => url('admin/reviews'),
            'modules' => ['reviews'],
        ],
        [
            'label' => 'Pending Reviews',
            'value' => $pendingReviewsCount,
            'meta' => 'Need moderation',
            'icon' => 'bi-hourglass-split',
            'tone' => 'ember',
            'url' => url('admin/reviews'),
            'modules' => ['reviews'],
        ],
    ];

    $secondaryStats = [
        [
            'label' => 'Banners',
            'value' => $bannersCount,
            'meta' => 'Campaign assets',
            'icon' => 'bi-image',
            'tone' => 'ocean',
            'url' => url('admin/banners'),
            'modules' => ['banners'],
        ],
        [
            'label' => 'Filters',
            'value' => $filtersCount,
            'meta' => 'Discovery controls',
            'icon' => 'bi-sliders2',
            'tone' => 'teal',
            'url' => url('admin/filters'),
            'modules' => ['filters'],
        ],
    ];

    if ($isAdmin) {
        $secondaryStats[] = [
            'label' => 'Subadmins',
            'value' => $subadminsCount,
            'meta' => 'Team seats',
            'icon' => 'bi-person-gear',
            'tone' => 'gold',
            'url' => url('admin/subadmins'),
            'modules' => ['subadmins'],
        ];
    } else {
        $secondaryStats[] = [
            'label' => 'Assigned Modules',
            'value' => $accessibleModulesCount,
            'meta' => 'Workspace access',
            'icon' => 'bi-grid',
            'tone' => 'gold',
            'url' => url('admin/dashboard'),
            'always' => true,
        ];
    }

    $quickLinks = [
        [
            'label' => 'Open Catalogue',
            'meta' => 'Jump into products and categories',
            'icon' => 'bi-box-seam',
            'url' => url('admin/products'),
            'modules' => ['products', 'categories'],
        ],
        [
            'label' => 'Moderate Reviews',
            'meta' => 'Clear pending customer feedback',
            'icon' => 'bi-stars',
            'url' => url('admin/reviews'),
            'modules' => ['reviews'],
        ],
        [
            'label' => 'Manage Customers',
            'meta' => 'Review account activity and profiles',
            'icon' => 'bi-people-fill',
            'url' => url('admin/users'),
            'modules' => ['users'],
        ],
        [
            'label' => 'Refresh Banners',
            'meta' => 'Update campaign creatives and promos',
            'icon' => 'bi-megaphone',
            'url' => url('admin/banners'),
            'modules' => ['banners'],
        ],
        [
            'label' => 'Tune Discovery',
            'meta' => 'Adjust product filters and facets',
            'icon' => 'bi-sliders2',
            'url' => url('admin/filters'),
            'modules' => ['filters'],
        ],
    ];

    if ($isAdmin) {
        $quickLinks[] = [
            'label' => 'Review Team Access',
            'meta' => 'Update subadmin permissions',
            'icon' => 'bi-shield-check',
            'url' => url('admin/subadmins'),
            'modules' => ['subadmins'],
        ];
    } else {
        $quickLinks[] = [
            'label' => 'Update Profile',
            'meta' => 'Keep your admin details current',
            'icon' => 'bi-person-vcard',
            'url' => url('admin/update-details'),
            'always' => true,
        ];
    }

    $heroMetrics = [
        [
            'value' => $accessibleModulesCount,
            'label' => 'modules in reach',
            'always' => true,
        ],
        [
            'value' => $pendingReviewsCount,
            'label' => 'reviews awaiting action',
            'modules' => ['reviews'],
        ],
        [
            'value' => $productsCount,
            'label' => 'products in catalog',
            'modules' => ['products'],
        ],
        [
            'value' => $usersCount,
            'label' => 'customer accounts',
            'modules' => ['users'],
        ],
        [
            'value' => $brandsCount,
            'label' => 'brands in network',
            'modules' => ['brands'],
        ],
        [
            'value' => $bannersCount,
            'label' => 'banners in rotation',
            'modules' => ['banners'],
        ],
        [
            'value' => $filtersCount,
            'label' => 'filters available',
            'modules' => ['filters'],
        ],
    ];

    $primaryStats = array_values(array_filter(
        $primaryStats,
        fn ($item) => $canView($item['modules'] ?? [], $item['always'] ?? false)
    ));

    $secondaryStats = array_values(array_filter(
        $secondaryStats,
        fn ($item) => $canView($item['modules'] ?? [], $item['always'] ?? false)
    ));

    $quickLinks = array_values(array_filter(
        $quickLinks,
        fn ($item) => $canView($item['modules'] ?? [], $item['always'] ?? false)
    ));

    $heroMetrics = array_slice(array_values(array_filter(
        $heroMetrics,
        fn ($item) => $canView($item['modules'] ?? [], $item['always'] ?? false)
    )), 0, 3);

    $showSubadminNotice = !$isAdmin;
    $showTrendChart = !empty($dashboardCharts['trend']['series']);
    $showReviewHealthChart = !empty($dashboardCharts['reviewHealth']['series']);
    $showOrdersNote = !$ordersModuleAvailable && ($isAdmin || !empty($dashboardPermissions['products']) || !empty($dashboardPermissions['brands']));
    $showCategoryVolumeChart = !empty($dashboardCharts['categories']['series']);
    $showCatalogMixChart = !empty($dashboardCharts['catalogMix']['series']);
    $showVendorPerformanceTable = !empty($dashboardPermissions['brands']);
    $showCartDemandTable = !empty($dashboardPermissions['products']);
    $showLowStockTable = !empty($dashboardPermissions['products']);
    $showLatestReviewsTable = !empty($dashboardPermissions['reviews']);
    $showRecentUsersTable = !empty($dashboardPermissions['users']);
    $hasAnalyticsWidgets = $showTrendChart
        || $showReviewHealthChart
        || $showCategoryVolumeChart
        || $showCatalogMixChart
        || $showVendorPerformanceTable
        || $showCartDemandTable
        || $showLowStockTable
        || $showLatestReviewsTable
        || $showRecentUsersTable;

@endphp

<main class="app-main admin-dashboard-page">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-8">
                    <h3 class="mb-0">{{ $dashboardTitle }}</h3>
                </div>
                <div class="col-sm-4">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ url('admin/dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            @if(Session::has('success_message'))
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <strong>Success!</strong> {{ Session::get('success_message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(Session::has('error_message'))
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <strong>Error!</strong> {{ Session::get('error_message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($showSubadminNotice)
                <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                    <strong>Role-based view:</strong> only charts, tables, and quick actions tied to modules assigned to this subadmin role are shown.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row g-4 mb-4">
                <div class="col-xl-8">
                    <section class="admin-dashboard-hero-card">
                        <div class="admin-dashboard-hero-card__content">
                            <span class="admin-dashboard-eyebrow">{{ $roleLabel }}</span>
                            <h1 class="admin-dashboard-hero-card__title">{{ $dashboardTitle }}</h1>
                            <p class="admin-dashboard-hero-card__subtitle">{{ $dashboardSubtitle }}</p>

                            <div class="admin-dashboard-hero-card__metrics">
                                @foreach($heroMetrics as $metric)
                                    <div class="admin-dashboard-chip">
                                        <span class="admin-dashboard-chip__value">{{ $metric['value'] }}</span>
                                        <span class="admin-dashboard-chip__label">{{ $metric['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="admin-dashboard-hero-card__meta">
                            <span><i class="bi bi-person-circle"></i>{{ $admin->name }}</span>
                            <span><i class="bi bi-envelope"></i>{{ $admin->email }}</span>
                            <span><i class="bi bi-calendar-event"></i>{{ now()->format('D, d M Y') }}</span>
                        </div>
                    </section>
                </div>

                <div class="col-xl-4">
                    <section class="admin-dashboard-focus-card">
                        <span class="admin-dashboard-eyebrow">{{ $focusCard['eyebrow'] }}</span>
                        <h2>{{ $focusCard['value'] }}</h2>
                        <p>{{ $focusCard['description'] }}</p>
                        <a href="{{ $focusCard['url'] }}" class="admin-dashboard-action-link">
                            {{ $focusCard['action_label'] }}
                            <i class="bi bi-arrow-up-right"></i>
                        </a>

                        @if(!empty($secondaryStats))
                            <div class="admin-dashboard-focus-card__list">
                                @foreach($secondaryStats as $item)
                                    <div class="admin-dashboard-mini-stat">
                                        <span class="admin-dashboard-mini-stat__label">{{ $item['label'] }}</span>
                                        <span class="admin-dashboard-mini-stat__value">{{ $item['value'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>
            </div>

            @if(!empty($primaryStats))
                <div class="row g-4 mb-4">
                    @foreach($primaryStats as $item)
                        <div class="col-12 col-md-6 col-xl-4">
                            <a href="{{ $item['url'] }}" class="admin-dashboard-stat-card admin-dashboard-stat-card--{{ $item['tone'] }}">
                                <span class="admin-dashboard-stat-card__icon">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                </span>
                                <div class="admin-dashboard-stat-card__body">
                                    <span class="admin-dashboard-stat-card__label">{{ $item['label'] }}</span>
                                    <strong class="admin-dashboard-stat-card__value">{{ $item['value'] }}</strong>
                                    <span class="admin-dashboard-stat-card__meta">{{ $item['meta'] }}</span>
                                </div>
                                <i class="bi bi-arrow-up-right admin-dashboard-stat-card__arrow"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(!$hasAnalyticsWidgets && $showSubadminNotice)
                <div class="row g-4 mb-4">
                    <div class="col-12">
                        <section class="admin-dashboard-note-card">
                            <span class="admin-dashboard-eyebrow admin-dashboard-eyebrow--dark">Analytics Scope</span>
                            <h3>No analytics widgets are available for this role yet</h3>
                            <p>
                                Assign products, brands, categories, users, reviews, banners, or filters access to unlock more dashboard data for this subadmin account.
                            </p>
                        </section>
                    </div>
                </div>
            @endif

            @if($showTrendChart || $showReviewHealthChart || $showOrdersNote)
                <div class="row g-4 mb-4">
                    @if($showTrendChart)
                        <div class="{{ ($showReviewHealthChart || $showOrdersNote) ? 'col-xl-8' : 'col-12' }}">
                            <section class="admin-dashboard-panel admin-dashboard-panel--chart">
                                <div class="admin-dashboard-panel__header">
                                    <div>
                                        <span class="admin-dashboard-eyebrow admin-dashboard-eyebrow--dark">Business Trends</span>
                                        <h2>Growth Across the Last 6 Months</h2>
                                    </div>
                                </div>
                                <div id="business-trend-chart" class="admin-dashboard-chart"></div>
                            </section>
                        </div>
                    @endif

                    @if($showReviewHealthChart || $showOrdersNote)
                        <div class="{{ $showTrendChart ? 'col-xl-4' : 'col-12' }}">
                            <div class="row g-4">
                                @if($showReviewHealthChart)
                                    <div class="col-12">
                                        <section class="admin-dashboard-panel admin-dashboard-panel--chart">
                                            <div class="admin-dashboard-panel__header">
                                                <div>
                                                    <span class="admin-dashboard-eyebrow admin-dashboard-eyebrow--dark">Review Health</span>
                                                    <h2>Approval Balance</h2>
                                                </div>
                                            </div>
                                            <div id="review-health-chart" class="admin-dashboard-chart admin-dashboard-chart--compact"></div>
                                        </section>
                                    </div>
                                @endif

                                @if($showOrdersNote)
                                    <div class="col-12">
                                        <section class="admin-dashboard-note-card">
                                            <span class="admin-dashboard-eyebrow admin-dashboard-eyebrow--dark">Orders Note</span>
                                            <h3>Order analytics are not available yet</h3>
                                            <p>
                                                This build has carts but no persisted orders module, so the dashboard currently uses cart demand as the closest buying signal.
                                            </p>
                                        </section>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            @if($showCategoryVolumeChart || $showCatalogMixChart)
                <div class="row g-4 mb-4">
                    @if($showCategoryVolumeChart)
                        <div class="{{ $showCatalogMixChart ? 'col-xl-7' : 'col-12' }}">
                            <section class="admin-dashboard-panel admin-dashboard-panel--chart">
                                <div class="admin-dashboard-panel__header">
                                    <div>
                                        <span class="admin-dashboard-eyebrow admin-dashboard-eyebrow--dark">Category Volume</span>
                                        <h2>Top Categories by Product Count</h2>
                                    </div>
                                </div>
                                <div id="category-volume-chart" class="admin-dashboard-chart admin-dashboard-chart--medium"></div>
                            </section>
                        </div>
                    @endif

                    @if($showCatalogMixChart)
                        <div class="{{ $showCategoryVolumeChart ? 'col-xl-5' : 'col-12' }}">
                            <section class="admin-dashboard-panel admin-dashboard-panel--chart">
                                <div class="admin-dashboard-panel__header">
                                    <div>
                                        <span class="admin-dashboard-eyebrow admin-dashboard-eyebrow--dark">Catalogue Mix</span>
                                        <h2>Inventory Composition</h2>
                                    </div>
                                </div>
                                <div id="catalog-mix-chart" class="admin-dashboard-chart admin-dashboard-chart--medium"></div>
                            </section>
                        </div>
                    @endif
                </div>
            @endif

            @if($showVendorPerformanceTable || $showCartDemandTable)
                <div class="row g-4 mb-4">
                    @if($showVendorPerformanceTable)
                        <div class="{{ $showCartDemandTable ? 'col-xl-6' : 'col-12' }}">
                            <section class="admin-dashboard-panel admin-dashboard-panel--table">
                                <div class="admin-dashboard-panel__header">
                                    <div>
                                        <span class="admin-dashboard-eyebrow admin-dashboard-eyebrow--dark">Vendor Intelligence</span>
                                        <h2>Brand Performance Table</h2>
                                    </div>
                                </div>

                                <div class="table-responsive admin-dashboard-table-wrap">
                                    <table class="table admin-dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>Vendor</th>
                                                <th>Products</th>
                                                <th>Active</th>
                                                <th>Featured</th>
                                                <th>Cart Qty</th>
                                                <th>Avg Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($vendorPerformance as $vendor)
                                                <tr>
                                                    <td>
                                                        <div class="admin-dashboard-table-title">{{ $vendor->name }}</div>
                                                        <div class="admin-dashboard-table-meta">
                                                            {{ $vendor->last_cart_at ? \Carbon\Carbon::parse($vendor->last_cart_at)->format('d M Y') : 'No cart activity yet' }}
                                                        </div>
                                                    </td>
                                                    <td>{{ $vendor->total_products }}</td>
                                                    <td>{{ $vendor->active_products }}</td>
                                                    <td>{{ $vendor->featured_products }}</td>
                                                    <td>{{ $vendor->cart_qty }}</td>
                                                    <td>KES {{ number_format((float) $vendor->avg_final_price, 0) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">No brand/vendor data available yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </div>
                    @endif

                    @if($showCartDemandTable)
                        <div class="{{ $showVendorPerformanceTable ? 'col-xl-6' : 'col-12' }}">
                            <section class="admin-dashboard-panel admin-dashboard-panel--table">
                                <div class="admin-dashboard-panel__header">
                                    <div>
                                        <span class="admin-dashboard-eyebrow admin-dashboard-eyebrow--dark">Buying Intent</span>
                                        <h2>Most Added to Cart</h2>
                                    </div>
                                </div>

                                <div class="table-responsive admin-dashboard-table-wrap">
                                    <table class="table admin-dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Brand</th>
                                                <th>Cart Qty</th>
                                                <th>Events</th>
                                                <th>Last Added</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($cartDemandProducts as $item)
                                                <tr>
                                                    <td>
                                                        <div class="admin-dashboard-table-title">{{ $item->product_name }}</div>
                                                    </td>
                                                    <td>{{ $item->brand_name }}</td>
                                                    <td>{{ $item->cart_qty }}</td>
                                                    <td>{{ $item->cart_events }}</td>
                                                    <td>{{ $item->last_added_at ? \Carbon\Carbon::parse($item->last_added_at)->format('d M Y') : '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">No cart demand data available yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </div>
                    @endif
                </div>
            @endif

            @if($showLowStockTable || $showLatestReviewsTable)
                <div class="row g-4 mb-4">
                    @if($showLowStockTable)
                        <div class="{{ $showLatestReviewsTable ? 'col-xl-6' : 'col-12' }}">
                            <section class="admin-dashboard-panel admin-dashboard-panel--table">
                                <div class="admin-dashboard-panel__header">
                                    <div>
                                        <span class="admin-dashboard-eyebrow admin-dashboard-eyebrow--dark">Inventory Risk</span>
                                        <h2>Low Stock Watch</h2>
                                    </div>
                                </div>

                                <div class="table-responsive admin-dashboard-table-wrap">
                                    <table class="table admin-dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Category</th>
                                                <th>Brand</th>
                                                <th>Stock</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($lowStockProducts as $product)
                                                <tr>
                                                    <td>
                                                        <div class="admin-dashboard-table-title">{{ $product->product_name }}</div>
                                                        <div class="admin-dashboard-table-meta">KES {{ number_format((float) $product->final_price, 0) }}</div>
                                                    </td>
                                                    <td>{{ $product->category->name ?? '-' }}</td>
                                                    <td>{{ $product->brand->name ?? 'Unbranded' }}</td>
                                                    <td>{{ $product->stock }}</td>
                                                    <td>
                                                        <span class="admin-dashboard-badge {{ $product->status == 1 ? 'admin-dashboard-badge--success' : 'admin-dashboard-badge--muted' }}">
                                                            {{ $product->status == 1 ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">No products available for stock analysis.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </div>
                    @endif

                    @if($showLatestReviewsTable)
                        <div class="{{ $showLowStockTable ? 'col-xl-6' : 'col-12' }}">
                            <section class="admin-dashboard-panel admin-dashboard-panel--table">
                                <div class="admin-dashboard-panel__header">
                                    <div>
                                        <span class="admin-dashboard-eyebrow admin-dashboard-eyebrow--dark">Moderation Feed</span>
                                        <h2>Latest Reviews</h2>
                                    </div>
                                </div>

                                <div class="table-responsive admin-dashboard-table-wrap">
                                    <table class="table admin-dashboard-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Customer</th>
                                                <th>Rating</th>
                                                <th>Status</th>
                                                <th>Message</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($latestReviews as $review)
                                                <tr>
                                                    <td>{{ $review->product->product_name ?? '-' }}</td>
                                                    <td>{{ $review->user->name ?? $review->user->email ?? 'Guest' }}</td>
                                                    <td>{{ $review->rating }}/5</td>
                                                    <td>
                                                        <span class="admin-dashboard-badge {{ $review->status == 1 ? 'admin-dashboard-badge--success' : 'admin-dashboard-badge--warning' }}">
                                                            {{ $review->status == 1 ? 'Approved' : 'Pending' }}
                                                        </span>
                                                    </td>
                                                    <td>{{ \Illuminate\Support\Str::limit($review->review ?? 'No review text', 56) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">No reviews available yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </div>
                    @endif
                </div>
            @endif

            <div class="row g-4">
                <div class="{{ $showRecentUsersTable ? 'col-xl-7' : 'col-12' }}">
                    <section class="admin-dashboard-panel">
                        <div class="admin-dashboard-panel__header">
                            <div>
                                <span class="admin-dashboard-eyebrow">Quick Actions</span>
                                <h2>Move Through Daily Work</h2>
                            </div>
                        </div>

                        <div class="admin-dashboard-quick-grid">
                            @foreach($quickLinks as $link)
                                <a href="{{ $link['url'] }}" class="admin-dashboard-quick-link">
                                    <span class="admin-dashboard-quick-link__icon">
                                        <i class="bi {{ $link['icon'] }}"></i>
                                    </span>
                                    <span class="admin-dashboard-quick-link__body">
                                        <span class="admin-dashboard-quick-link__label">{{ $link['label'] }}</span>
                                        <span class="admin-dashboard-quick-link__meta">{{ $link['meta'] }}</span>
                                    </span>
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            @endforeach
                        </div>
                    </section>
                </div>

                @if($showRecentUsersTable)
                    <div class="col-xl-5">
                        <section class="admin-dashboard-panel admin-dashboard-panel--table">
                            <div class="admin-dashboard-panel__header">
                                <div>
                                    <span class="admin-dashboard-eyebrow admin-dashboard-eyebrow--dark">Customer Growth</span>
                                    <h2>Recent User Registrations</h2>
                                </div>
                            </div>

                            <div class="table-responsive admin-dashboard-table-wrap">
                                <table class="table admin-dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentUsers as $user)
                                            <tr>
                                                <td>{{ $user->name ?? 'Unnamed user' }}</td>
                                                <td>{{ $user->email }}</td>
                                                <td>
                                                    <span class="admin-dashboard-badge {{ $user->status ? 'admin-dashboard-badge--success' : 'admin-dashboard-badge--muted' }}">
                                                        {{ $user->status ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td>{{ optional($user->created_at)->format('d M Y') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">No user records available yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>

<script>
    window.adminDashboardCharts = @json($dashboardCharts);
</script>
@endsection
