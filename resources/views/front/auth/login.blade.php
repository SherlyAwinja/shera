@extends('front.layout.layout')

@section('content')
<div class="front-luxe-page auth-page login-page">
    <div class="container-fluid bg-secondary mb-5 front-page-hero auth-page-hero">
        <div class="front-page-hero-inner d-flex flex-column align-items-center justify-content-center text-center">
            <span class="front-page-eyebrow">Account Access</span>
            <h1 class="front-page-title mb-3">Welcome back to SHERA</h1>
            <p class="front-page-subtitle mb-3">Sign in to review orders, save favorites, and continue checkout without friction.</p>
            <div class="front-page-breadcrumb d-inline-flex flex-wrap justify-content-center">
                <p class="m-0"><a href="{{ url('/') }}">Home</a></p>
                <p class="m-0 px-2">/</p>
                <p class="m-0">Login</p>
            </div>
        </div>
    </div>

    <div class="container-fluid front-page-shell pb-5">
        <div class="row px-xl-5 justify-content-center">
            <div class="col-xl-10">
                <div class="auth-shell">
                    <div class="auth-aside">
                        <span class="auth-kicker">Member Access</span>
                        <h2 class="auth-title">Step back into your account with everything ready to go.</h2>
                        <p class="auth-copy">Your saved profile keeps checkout faster and your order history within reach.</p>

                        <div class="auth-feature-list">
                            <div class="auth-feature-item">
                                <i class="fa fa-shopping-bag"></i>
                                <span>Pick up right where your last order left off.</span>
                            </div>
                            <div class="auth-feature-item">
                                <i class="fa fa-heart"></i>
                                <span>Keep favorites and future purchases in one place.</span>
                            </div>
                            <div class="auth-feature-item">
                                <i class="fa fa-truck"></i>
                                <span>Track purchases with less back-and-forth.</span>
                            </div>
                        </div>
                    </div>

                    <div class="auth-form-panel">
                        <div class="auth-panel-head">
                            <span class="auth-panel-kicker">Sign In</span>
                            <h3 class="auth-panel-title">Login to Your Account</h3>
                        </div>

                        <div id="loginSuccess">
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                        </div>

                        <form name="loginForm" id="loginForm" method="POST" action="{{ route('user.login.post', [], false) }}" class="contact-form auth-form" novalidate>
                            @csrf

                            <div class="control-group mb-3 auth-field">
                                <label class="auth-label" for="loginEmail">Email Address</label>
                                <input type="email" class="form-control auth-input" name="email" id="loginEmail" placeholder="you@example.com" value="{{ old('email') }}" required>
                                <p class="help-block text-danger auth-error" data-error-for="email">@error('email'){{ $message }}@enderror</p>
                            </div>

                            <div class="control-group mb-3 auth-field">
                                <label class="auth-label" for="loginPassword">Password</label>
                                <input type="password" class="form-control auth-input" name="password" id="loginPassword" placeholder="Enter your password" required>
                                <p class="help-block text-danger auth-error" data-error-for="password">@error('password'){{ $message }}@enderror</p>
                            </div>

                            <div class="control-group mb-4 auth-field">
                                <label class="auth-label d-block">Login as</label>
                                <div class="auth-choice-grid">
                                    <div class="form-check auth-choice-card">
                                        <input class="form-check-input" type="radio" name="user_type" id="loginCustomer" value="Customer" {{ old('user_type', 'Customer') === 'Customer' ? 'checked' : '' }}>
                                        <label class="form-check-label auth-choice-label" for="loginCustomer">
                                            <span class="auth-choice-title">Customer</span>
                                            <small>Shop, track orders, and save favorites.</small>
                                        </label>
                                    </div>

                                    <div class="form-check auth-choice-card">
                                        <input class="form-check-input" type="radio" name="user_type" id="loginVendor" value="Vendor" {{ old('user_type') === 'Vendor' ? 'checked' : '' }}>
                                        <label class="form-check-label auth-choice-label" for="loginVendor">
                                            <span class="auth-choice-title">Vendor</span>
                                            <small>Access account tools built for selling.</small>
                                        </label>
                                    </div>
                                </div>
                                <p class="help-block text-danger auth-error" data-error-for="user_type">@error('user_type'){{ $message }}@enderror</p>
                            </div>

                            <div>
                                <button class="btn btn-primary py-3 px-4 w-100 auth-submit-btn" type="submit" id="loginButton">Login</button>
                            </div>
                        </form>

                        <p class="mt-3 text-center auth-switch-copy">
                            <a href="{{ route('user.password.forgot', [], false) }}">Forgot your password?</a>
                        </p>

                        <p class="mt-4 text-center auth-switch-copy">
                            Don't have an account? <a href="{{ route('user.register', [], false) }}">Register here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
