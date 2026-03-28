@extends('front.layout.layout')

@section('content')
<div class="front-luxe-page auth-page register-page">
    <div class="container-fluid bg-secondary mb-5 front-page-hero auth-page-hero">
        <div class="front-page-hero-inner d-flex flex-column align-items-center justify-content-center text-center">
            <span class="front-page-eyebrow">Create Account</span>
            <h1 class="front-page-title mb-3">Join SHERA with a polished start</h1>
            <p class="front-page-subtitle mb-3">Open your profile once and make future shopping or selling feel far more streamlined.</p>
            <div class="front-page-breadcrumb d-inline-flex flex-wrap justify-content-center">
                <p class="m-0"><a href="{{ url('/') }}">Home</a></p>
                <p class="m-0 px-2">/</p>
                <p class="m-0">Register</p>
            </div>
        </div>
    </div>

    <div class="container-fluid front-page-shell pb-5">
        <div class="row px-xl-5 justify-content-center">
            <div class="col-xl-10">
                <div class="auth-shell">
                    <div class="auth-aside">
                        <span class="auth-kicker">New Profile</span>
                        <h2 class="auth-title">Build an account that keeps your next steps organized.</h2>
                        <p class="auth-copy">Whether you are buying or selling, one profile keeps your experience cleaner from the first session.</p>

                        <div class="auth-feature-list">
                            <div class="auth-feature-item">
                                <i class="fa fa-user-check"></i>
                                <span>Create a profile for quicker future checkout.</span>
                            </div>
                            <div class="auth-feature-item">
                                <i class="fa fa-store"></i>
                                <span>Choose the account type that matches your role.</span>
                            </div>
                            <div class="auth-feature-item">
                                <i class="fa fa-lock"></i>
                                <span>Start with secure credentials and cleaner access.</span>
                            </div>
                        </div>
                    </div>

                    <div class="auth-form-panel">
                        <div class="auth-panel-head">
                            <span class="auth-panel-kicker">Get Started</span>
                            <h3 class="auth-panel-title">Create an Account</h3>
                        </div>

                        <div id="registerSuccess"></div>

                        <form id="registerForm" method="POST" action="{{ route('user.register.post', [], false) }}" class="contact-form auth-form" novalidate>
                            @csrf

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="control-group mb-3 auth-field">
                                        <label class="auth-label" for="name">Full Name</label>
                                        <input type="text" class="form-control auth-input" name="name" id="name" placeholder="Your full name" value="{{ old('name') }}" required>
                                        <p class="help-block text-danger auth-error" data-error-for="name">@error('name'){{ $message }}@enderror</p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="control-group mb-3 auth-field">
                                        <label class="auth-label" for="email">Email Address</label>
                                        <input type="email" class="form-control auth-input" name="email" id="email" placeholder="you@example.com" value="{{ old('email') }}" required>
                                        <p class="help-block text-danger auth-error" data-error-for="email">@error('email'){{ $message }}@enderror</p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="col-md-6">
                                    <div class="control-group mb-3 auth-field">
                                        <label class="auth-label" for="password">Password</label>
                                        <input type="password" class="form-control auth-input" name="password" id="password" placeholder="Create a password" required>
                                        <p class="help-block text-danger auth-error" data-error-for="password">@error('password'){{ $message }}@enderror</p>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="control-group mb-3 auth-field">
                                        <label class="auth-label" for="password_confirmation">Confirm Password</label>
                                        <input type="password" class="form-control auth-input" name="password_confirmation" id="password_confirmation" placeholder="Repeat your password" required>
                                        <p class="help-block text-danger auth-error" data-error-for="password_confirmation">@error('password_confirmation'){{ $message }}@enderror</p>
                                    </div>
                                </div>
                            </div>

                            <div class="auth-helper-copy mb-4">Use a strong password you can remember easily.</div>

                            <div class="control-group mb-4 auth-field">
                                <label class="auth-label d-block">Register as</label>
                                <div class="auth-choice-grid">
                                    <div class="form-check auth-choice-card">
                                        <input class="form-check-input" type="radio" name="user_type" id="regCustomer" value="Customer" {{ old('user_type', 'Customer') === 'Customer' ? 'checked' : '' }}>
                                        <label class="form-check-label auth-choice-label" for="regCustomer">
                                            <span class="auth-choice-title">Customer</span>
                                            <small>Discover, save, and purchase with ease.</small>
                                        </label>
                                    </div>

                                    <div class="form-check auth-choice-card">
                                        <input class="form-check-input" type="radio" name="user_type" id="regVendor" value="Vendor" {{ old('user_type') === 'Vendor' ? 'checked' : '' }}>
                                        <label class="form-check-label auth-choice-label" for="regVendor">
                                            <span class="auth-choice-title">Vendor</span>
                                            <small>Access tools for catalog and storefront work.</small>
                                        </label>
                                    </div>
                                </div>
                                <p class="help-block text-danger auth-error" data-error-for="user_type">@error('user_type'){{ $message }}@enderror</p>
                            </div>

                            <div>
                                <button class="btn btn-primary py-3 px-4 w-100 auth-submit-btn" type="submit" id="registerButton">Register</button>
                            </div>
                        </form>

                        <p class="mt-4 text-center auth-switch-copy">
                            Already have an account? <a href="{{ route('user.login', [], false) }}">Login here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
