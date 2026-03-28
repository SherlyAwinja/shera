<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laravel 12 Multi-Vendor E-commerce Template by SiteMakers.in</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="Laravel 12, Multi-Vendor E-commerce, Laravel E-commerce Template, Bootstrap Laravel, Laravel Admin Panel, Laravel Marketplace, SiteMakers, Stack Developers">
    <meta name="description" content="Download the SiteMakers Laravel 12 Multi-Vendor E-commerce Template – a clean and scalable Laravel project built with Bootstrap, Blade, and a powerful admin panel. Ideal for learning and prototyping e-commerce websites.">

    <!-- Favicon -->
    <link href="{{ asset('favicon.ico') }}" rel="icon">

    @include('front.layout.styles')
    @stack('styles')

</head>

<body>

    @include('front.layout.header')

    @yield('content')

    @include('front.layout.footer')

    @include('front.layout.scripts')
    @stack('scripts')
</body>

</html>
