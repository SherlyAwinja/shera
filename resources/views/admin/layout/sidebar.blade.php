@php
    $currentPage = Session::get('page');
    $admin = Auth::guard('admin')->user();
    $adminRole = strtolower((string) optional($admin)->role);
    $roleLabel = $adminRole === 'admin' ? 'Administrator' : 'Subadmin';
    $profileImage = !empty($admin?->image) ? asset('admin/images/photos/' . $admin->image) : null;

    $initials = '';
    foreach (preg_split('/\s+/', trim((string) ($admin?->name ?? 'Admin'))) as $part) {
        if ($part === '') {
            continue;
        }
        $initials .= strtoupper(substr($part, 0, 1));
        if (strlen($initials) >= 2) {
            break;
        }
    }
    $initials = $initials ?: 'AD';

    $workspaceItems = [
        [
            'label' => 'Dashboard',
            'hint' => 'Overview and activity',
            'icon' => 'bi-speedometer2',
            'url' => url('admin/dashboard'),
            'pages' => ['dashboard'],
        ],
        [
            'label' => 'Profile',
            'hint' => 'Personal details',
            'icon' => 'bi-person-vcard',
            'url' => url('admin/update-details'),
            'pages' => ['update-details'],
        ],
        [
            'label' => 'Security',
            'hint' => 'Password and access',
            'icon' => 'bi-shield-lock',
            'url' => url('admin/update-password'),
            'pages' => ['update-password'],
        ],
    ];

    if ($adminRole === 'admin') {
        $workspaceItems[] = [
            'label' => 'Subadmins',
            'hint' => 'Team permissions',
            'icon' => 'bi-people',
            'url' => url('admin/subadmins'),
            'pages' => ['subadmins'],
        ];
    }

    $menuGroups = [
        [
            'title' => 'Workspace',
            'group_icon' => 'bi-grid-1x2-fill',
            'pages' => ['dashboard', 'update-password', 'update-details', 'subadmins'],
            'items' => $workspaceItems,
        ],
        [
            'title' => 'Catalogue',
            'group_icon' => 'bi-box-seam-fill',
            'pages' => ['categories', 'brands', 'products', 'filters', 'filters_values'],
            'items' => [
                [
                    'label' => 'Categories',
                    'hint' => 'Structure your store',
                    'icon' => 'bi-diagram-3',
                    'url' => url('admin/categories'),
                    'pages' => ['categories'],
                ],
                [
                    'label' => 'Brands',
                    'hint' => 'Vendor identities',
                    'icon' => 'bi-bookmark-star',
                    'url' => url('admin/brands'),
                    'pages' => ['brands'],
                ],
                [
                    'label' => 'Products',
                    'hint' => 'Inventory and media',
                    'icon' => 'bi-bag-check',
                    'url' => url('admin/products'),
                    'pages' => ['products'],
                ],
                [
                    'label' => 'Filters',
                    'hint' => 'Facets and attributes',
                    'icon' => 'bi-sliders2',
                    'url' => url('admin/filters'),
                    'pages' => ['filters', 'filters_values'],
                ],
            ],
        ],
        [
            'title' => 'Campaigns',
            'group_icon' => 'bi-megaphone-fill',
            'pages' => ['banners', 'coupons'],
            'items' => [
                [
                    'label' => 'Banners',
                    'hint' => 'Hero and promo slots',
                    'icon' => 'bi-image',
                    'url' => url('admin/banners'),
                    'pages' => ['banners'],
                ],
                [
                    'label' => 'Coupons',
                    'hint' => 'Discount rules and codes',
                    'icon' => 'bi-ticket-perforated',
                    'url' => url('admin/coupons'),
                    'pages' => ['coupons'],
                ],
            ],
        ],
        [
            'title' => 'Customers',
            'group_icon' => 'bi-people-fill',
            'pages' => ['users', 'reviews', 'wallets', 'orders'],
            'items' => [
                [
                    'label' => 'Users',
                    'hint' => 'Accounts and profiles',
                    'icon' => 'bi-person-lines-fill',
                    'url' => url('admin/users'),
                    'pages' => ['users'],
                ],
                [
                    'label' => 'Reviews',
                    'hint' => 'Feedback moderation',
                    'icon' => 'bi-chat-square-heart',
                    'url' => url('admin/reviews'),
                    'pages' => ['reviews'],
                ],
                [
                    'label' => 'Orders',
                    'hint' => 'Placed, paid, fulfilled',
                    'icon' => 'bi-receipt-cutoff',
                    'url' => url('admin/orders'),
                    'pages' => ['orders'],
                ],
                [
                    'label' => 'Wallets',
                    'hint' => 'Credits, debits, balances',
                    'icon' => 'bi-wallet2',
                    'url' => url('admin/wallets'),
                    'pages' => ['wallets'],
                ],
            ],
        ],
    ];
@endphp

<aside class="app-sidebar admin-sidebar-redesign shadow" data-bs-theme="dark">
    <div class="sidebar-brand admin-sidebar-brand">
        <a href="{{ url('admin/dashboard') }}" class="brand-link admin-brand-link text-decoration-none">
            <div class="admin-brand-mark">
                <span class="admin-brand-mark__label">SH</span>
            </div>
            <div class="admin-brand-copy">
                <span class="admin-brand-copy__eyebrow">Shera Store</span>
                <span class="admin-brand-copy__title">{{ $adminRole === 'admin' ? 'Control Center' : 'Operations Desk' }}</span>
            </div>
        </a>
    </div>

    <div class="sidebar-wrapper d-flex flex-column">
        <div class="admin-sidebar-profile-card">
            <div class="admin-sidebar-profile-card__media">
                @if($profileImage)
                    <img src="{{ $profileImage }}" alt="{{ $admin->name }}" class="admin-sidebar-profile-card__image">
                @else
                    <span class="admin-sidebar-profile-card__initials">{{ $initials }}</span>
                @endif
            </div>
            <div class="admin-sidebar-profile-card__body">
                <span class="admin-sidebar-profile-card__name">{{ $admin->name ?? 'Admin User' }}</span>
                <span class="admin-sidebar-profile-card__meta">{{ $admin->email ?? 'admin@shera.local' }}</span>
                <span class="admin-sidebar-profile-card__badge">{{ $roleLabel }}</span>
            </div>
        </div>

        <nav class="mt-2 flex-grow-1">
            <ul class="nav sidebar-menu admin-sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                @foreach($menuGroups as $group)
                    @php
                        $isGroupOpen = in_array($currentPage, $group['pages']);
                    @endphp

                    <li class="nav-item admin-sidebar-group {{ $isGroupOpen ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link admin-sidebar-group__toggle {{ $isGroupOpen ? 'active' : '' }}">
                            <span class="admin-sidebar-icon-wrap">
                                <i class="nav-icon bi {{ $group['group_icon'] }}"></i>
                            </span>
                            <p>
                                <span class="admin-sidebar-copy">
                                    <span class="admin-sidebar-copy__title">{{ $group['title'] }}</span>
                                    <span class="admin-sidebar-copy__meta">{{ count($group['items']) }} sections</span>
                                </span>
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>

                        <ul class="nav nav-treeview admin-sidebar-submenu">
                            @foreach($group['items'] as $item)
                                <li class="nav-item">
                                    <a href="{{ $item['url'] }}" class="nav-link {{ in_array($currentPage, $item['pages']) ? 'active' : '' }}">
                                        <span class="admin-sidebar-subicon">
                                            <i class="nav-icon bi {{ $item['icon'] }}"></i>
                                        </span>
                                        <p>
                                            <span class="admin-sidebar-copy">
                                                <span class="admin-sidebar-copy__title">{{ $item['label'] }}</span>
                                                <span class="admin-sidebar-copy__meta">{{ $item['hint'] }}</span>
                                            </span>
                                        </p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="admin-sidebar-footer">
            <a href="{{ url('/') }}" class="admin-sidebar-footer__link" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>View storefront</span>
            </a>
            <a href="{{ url('admin/logout') }}" class="admin-sidebar-footer__link admin-sidebar-footer__link--danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign out</span>
            </a>
        </div>
    </div>
</aside>
