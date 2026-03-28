@extends('front.layout.layout')

@section('content')
<div class="front-luxe-page auth-page recovery-page reset-page">
    <div class="container-fluid bg-secondary mb-5 front-page-hero auth-page-hero">
        <div class="front-page-hero-inner d-flex flex-column align-items-center justify-content-center text-center">
            <span class="front-page-eyebrow">Password Reset</span>
            <h1 class="front-page-title mb-3">Create a new password and step back in with confidence</h1>
            <p class="front-page-subtitle mb-3">Use a strong password that meets the security rules and feels easy enough for you to remember later.</p>
            <div class="front-page-breadcrumb d-inline-flex flex-wrap justify-content-center">
                <p class="m-0"><a href="{{ url('/') }}">Home</a></p>
                <p class="m-0 px-2">/</p>
                <p class="m-0">Reset Password</p>
            </div>
        </div>
    </div>

    <div class="container-fluid front-page-shell pb-5">
        <div class="row px-xl-5 justify-content-center">
            <div class="col-xl-10">
                <div class="auth-shell">
                    <div class="auth-aside">
                        <span class="auth-kicker">Secure Update</span>
                        <h2 class="auth-title">Give your account a stronger key without slowing yourself down.</h2>
                        <p class="auth-copy">This final step replaces your old password with a stronger one. Once updated, you can continue shopping, reviewing orders, and moving through checkout without interruption.</p>

                        <div class="auth-feature-list">
                            <div class="auth-feature-item">
                                <i class="fa fa-lock"></i>
                                <span>Use a password with mixed case letters, numbers, and a symbol.</span>
                            </div>
                            <div class="auth-feature-item">
                                <i class="fa fa-user-shield"></i>
                                <span>Your reset token is tied to your email, so keep that address consistent.</span>
                            </div>
                            <div class="auth-feature-item">
                                <i class="fa fa-check-circle"></i>
                                <span>When the reset succeeds, your account is ready for immediate use again.</span>
                            </div>
                        </div>

                        <div class="auth-insight-card">
                            <span class="auth-insight-label">Password Guide</span>

                            <div class="auth-step-grid">
                                <div class="auth-step-chip">
                                    <strong>A</strong>
                                    <span>Make it at least 8 characters long.</span>
                                </div>

                                <div class="auth-step-chip">
                                    <strong>B</strong>
                                    <span>Blend uppercase, lowercase, numbers, and symbols.</span>
                                </div>

                                <div class="auth-step-chip">
                                    <strong>C</strong>
                                    <span>Avoid reusing an older password from another account.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="auth-form-panel">
                        <div class="auth-panel-head">
                            <span class="auth-panel-kicker">Step 2</span>
                            <h3 class="auth-panel-title">Set Your New Password</h3>
                        </div>

                        <div id="resetSuccess">
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                        </div>

                        <div class="auth-helper-copy mb-4">
                            Keep the email from your reset link here, then choose a new password that meets every security requirement.
                        </div>

                        <form name="resetForm" id="resetForm" method="POST" action="{{ route('user.password.reset.post', [], false) }}" class="contact-form auth-form" novalidate>
                            @csrf

                            <input type="hidden" name="token" value="{{ $token }}" />

                            <div class="control-group mb-3 auth-field">
                                <label class="auth-label" for="resetEmail">Email Address</label>
                                <input
                                    type="email"
                                    class="form-control auth-input"
                                    name="email"
                                    id="resetEmail"
                                    placeholder="you@example.com"
                                    value="{{ old('email', $email) }}"
                                    required
                                />
                                <p class="help-block text-danger auth-error" data-error-for="email">@error('email'){{ $message }}@enderror</p>
                            </div>

                            <div class="control-group mb-3 auth-field">
                                <label class="auth-label" for="resetPassword">New Password</label>
                                <input
                                    type="password"
                                    class="form-control auth-input"
                                    name="password"
                                    id="resetPassword"
                                    placeholder="Create a new password"
                                    required
                                />
                                <p class="help-block text-danger auth-error" data-error-for="password">@error('password'){{ $message }}@enderror</p>
                            </div>

                            <div class="control-group mb-4 auth-field">
                                <label class="auth-label" for="resetConfirm">Confirm Password</label>
                                <input
                                    type="password"
                                    class="form-control auth-input"
                                    name="password_confirmation"
                                    id="resetConfirm"
                                    placeholder="Repeat the new password"
                                    required
                                />
                                <p class="help-block text-danger auth-error" data-error-for="password_confirmation">@error('password_confirmation'){{ $message }}@enderror</p>
                            </div>

                            <ul class="auth-tips-list">
                                <li>
                                    <i class="fa fa-check"></i>
                                    <span>Use something unique enough that it is not reused elsewhere.</span>
                                </li>
                                <li>
                                    <i class="fa fa-check"></i>
                                    <span>Avoid predictable names, dates, or common word combinations.</span>
                                </li>
                            </ul>

                            <div class="mt-4">
                                <button
                                    class="btn btn-primary py-3 px-4 w-100 auth-submit-btn"
                                    type="submit"
                                    id="resetButton">
                                    Reset Password
                                </button>
                            </div>
                        </form>

                        <div class="auth-link-row">
                            <a href="{{ route('user.login', [], false) }}">Back to login</a>
                            <a href="{{ route('user.password.forgot', [], false) }}">Request another link</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
