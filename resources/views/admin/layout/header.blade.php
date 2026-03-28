@php
    $admin = Auth::guard('admin')->user();
    $isAdmin = strtolower((string) $admin->role) === 'admin';
    $pageKey = session('page', 'dashboard');
    $pageTitle = ucwords(str_replace(['_', '-'], ' ', (string) $pageKey));
    $workspaceLabel = $isAdmin ? 'Administrator Workspace' : 'Role Based Workspace';
    $roleLabel = $isAdmin ? 'Admin' : 'Subadmin';
    $memberSince = optional($admin->created_at)->format('M Y');
    $nameParts = preg_split('/\s+/', trim((string) ($admin->name ?? 'Admin'))) ?: ['Admin'];
    $initials = collect($nameParts)
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
    $profileImage = !empty($admin->image)
        ? asset('admin/images/photos/' . $admin->image)
        : null;
@endphp

<nav class="app-header navbar navbar-expand admin-app-header">
    <div class="container-fluid">
        <div class="admin-app-header__inner">
            <div class="admin-app-header__left">
                <a class="admin-app-header__toggle" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
                    <i class="bi bi-list"></i>
                </a>

                <div class="admin-app-header__context">
                    <span class="admin-app-header__eyebrow">
                        <i class="bi bi-stars"></i>
                        <span>{{ $workspaceLabel }}</span>
                    </span>

                    <div class="admin-app-header__title-row">
                        <h1 class="admin-app-header__title">{{ $pageTitle }}</h1>
                        <span class="admin-app-header__badge">{{ $roleLabel }}</span>
                    </div>
                </div>
            </div>

            <div class="admin-app-header__right">
                <a href="{{ url('/') }}" class="admin-app-header__utility d-none d-lg-inline-flex">
                    <i class="bi bi-shop-window"></i>
                    <span>View Storefront</span>
                </a>

                <a class="admin-app-header__utility admin-app-header__utility--icon" href="#" data-lte-toggle="fullscreen" aria-label="Toggle fullscreen">
                    <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                    <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
                </a>

                <div class="dropdown admin-app-header__user user-menu">
                    <a href="#" class="admin-app-header__user-trigger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        @if($profileImage)
                            <img src="{{ $profileImage }}" class="admin-app-header__avatar-image" alt="{{ $admin->name }}" />
                        @else
                            <span class="admin-app-header__avatar">{{ $initials }}</span>
                        @endif

                        <span class="admin-app-header__identity d-none d-md-flex">
                            <span class="admin-app-header__name">{{ $admin->name }}</span>
                            <span class="admin-app-header__role">{{ $roleLabel }} access</span>
                        </span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end admin-app-header__menu">
                        <li class="admin-app-header__menu-hero">
                            <div class="admin-app-header__menu-avatar">
                                @if($profileImage)
                                    <img src="{{ $profileImage }}" class="admin-app-header__avatar-image" alt="{{ $admin->name }}" />
                                @else
                                    <span class="admin-app-header__avatar">{{ $initials }}</span>
                                @endif
                            </div>

                            <div class="admin-app-header__menu-copy">
                                <h3 class="admin-app-header__menu-name">{{ $admin->name }}</h3>
                                <span class="admin-app-header__menu-email">{{ $admin->email }}</span>
                                <span class="admin-app-header__menu-meta">
                                    <i class="bi bi-calendar-event"></i>
                                    <span>Member since {{ $memberSince ?: 'recently' }}</span>
                                </span>
                            </div>
                        </li>

                        <li><hr class="dropdown-divider admin-app-header__menu-divider"></li>

                        <li class="admin-app-header__menu-links">
                            <a href="{{ url('admin/dashboard') }}" class="admin-app-header__menu-link">
                                <span class="admin-app-header__menu-link-main">
                                    <span class="admin-app-header__menu-icon"><i class="bi bi-speedometer2"></i></span>
                                    <span class="admin-app-header__menu-label">
                                        <strong>Dashboard</strong>
                                        <span>Return to the main control view</span>
                                    </span>
                                </span>
                                <i class="bi bi-chevron-right"></i>
                            </a>

                            <a href="{{ url('admin/update-details') }}" class="admin-app-header__menu-link">
                                <span class="admin-app-header__menu-link-main">
                                    <span class="admin-app-header__menu-icon"><i class="bi bi-person-vcard"></i></span>
                                    <span class="admin-app-header__menu-label">
                                        <strong>Profile Details</strong>
                                        <span>Update your account information</span>
                                    </span>
                                </span>
                                <i class="bi bi-chevron-right"></i>
                            </a>

                            <a href="{{ url('admin/update-password') }}" class="admin-app-header__menu-link">
                                <span class="admin-app-header__menu-link-main">
                                    <span class="admin-app-header__menu-icon"><i class="bi bi-shield-lock"></i></span>
                                    <span class="admin-app-header__menu-label">
                                        <strong>Security</strong>
                                        <span>Review password and sign-in settings</span>
                                    </span>
                                </span>
                                <i class="bi bi-chevron-right"></i>
                            </a>

                            <a href="{{ url('/') }}" class="admin-app-header__menu-link d-lg-none">
                                <span class="admin-app-header__menu-link-main">
                                    <span class="admin-app-header__menu-icon"><i class="bi bi-shop-window"></i></span>
                                    <span class="admin-app-header__menu-label">
                                        <strong>View Storefront</strong>
                                        <span>Open the customer-facing site</span>
                                    </span>
                                </span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>

                        <li><hr class="dropdown-divider admin-app-header__menu-divider"></li>

                        <li>
                            <a href="{{ url('admin/logout') }}" class="admin-app-header__menu-signout">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Sign out</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
