<!doctype html>
<html lang="en">
    <!--begin::Head-->
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>{{ config('app.name', 'Shera') }} | Admin Dashboard</title>
        <!--begin::Accessibility Meta Tags-->
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
        <meta name="color-scheme" content="light dark" />
        <meta name="theme-color" content="#007bff" media="(prefers-color-scheme: light)" />
        <meta name="theme-color" content="#1a1a1a" media="(prefers-color-scheme: dark)" />
        <!--end::Accessibility Meta Tags-->
        <!--begin::Primary Meta Tags-->
        <meta name="title" content="{{ config('app.name', 'Shera') }} | Admin Dashboard" />
        <meta name="author" content="{{ config('app.name', 'Shera') }}" />
        <meta
            name="description"
            content="Administrative dashboard for {{ config('app.name', 'Shera') }}."
            />
        <meta
            name="keywords"
            content="{{ config('app.name', 'Shera') }}, admin dashboard, store management, catalog management, customer management, reviews"
            />
        <!--end::Primary Meta Tags-->
        <!--begin::Accessibility Features-->
        <!-- Skip links will be dynamically added by accessibility.js -->
        <meta name="supported-color-schemes" content="light dark" />
        @include('admin.layout.styles')
    </head>
    <body class="layout-fixed sidebar-expand-lg sidebar-open bg-body-tertiary">
        <!--begin::App Wrapper-->
        <div class="app-wrapper">
            <!--begin::Header-->
            @include('admin.layout.header')
            <!--end::Header-->
            <!--begin::Sidebar-->
            @include('admin.layout.sidebar')
            <!--end::Sidebar-->
            <!--begin::App Main-->
            @yield('content')
            <!--end::App Main-->
            <!--begin::Footer-->
            @include('admin.layout.footer')
            <!--end::Footer-->
        </div>
        <!--end::App Wrapper-->
        <!--begin::Script-->
        @include('admin.layout.scripts')
        <!--end::Script-->
