@extends('front.layout.layout')

@section('content')
<div class="front-luxe-page auth-page recovery-page forgot-page">
    <div class="container-fluid bg-secondary mb-5 front-page-hero auth-page-hero">
        <div class="front-page-hero-inner d-flex flex-column align-items-center justify-content-center text-center">
            <span class="front-page-eyebrow">Account Recovery</span>
            <h1 class="front-page-title mb-3">Recover your SHERA access with a secure reset flow</h1>
            <p class="front-page-subtitle mb-3">We will send a fresh password reset link to your account email so you can get back in without friction.</p>
            <div class="front-page-breadcrumb d-inline-flex flex-wrap justify-content-center">
                <p class="m-0"><a href="{{ url('/') }}">Home</a></p>
                <p class="m-0 px-2">/</p>
                <p class="m-0">Forgot Password</p>
            </div>
        </div>
    </div>

    <div class="container-fluid front-page-shell pb-5">
        <div class="row px-xl-5 justify-content-center">
            <div class="col-xl-10">
                <div class="auth-shell">
                    <div class="auth-aside">
                        <span class="auth-kicker">Recovery Flow</span>
                        <h2 class="auth-title">A calm route back into your account starts with one verified email.</h2>
                        <p class="auth-copy">Use the email linked to your SHERA profile and we will send a time-limited reset link. If you request more than one, only the latest link is worth using.</p>

                        <div class="auth-feature-list">
                            <div class="auth-feature-item">
                                <i class="fa fa-envelope-open-text"></i>
                                <span>Receive a secure reset link in your inbox within moments.</span>
                            </div>
                            <div class="auth-feature-item">
                                <i class="fa fa-shield-alt"></i>
                                <span>The reset token expires automatically to keep account access protected.</span>
                            </div>
                            <div class="auth-feature-item">
                                <i class="fa fa-key"></i>
                                <span>Once you update your password, older reset links become useless.</span>
                            </div>
                        </div>

                        <div class="auth-insight-card">
                            <span class="auth-insight-label">What Happens Next</span>

                            <div class="auth-step-grid">
                                <div class="auth-step-chip">
                                    <strong>1</strong>
                                    <span>Enter the email tied to your account.</span>
                                </div>

                                <div class="auth-step-chip">
                                    <strong>2</strong>
                                    <span>Open the most recent reset email from SHERA.</span>
                                </div>

                                <div class="auth-step-chip">
                                    <strong>3</strong>
                                    <span>Choose a new password and sign back in.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="auth-form-panel">
                        <div class="auth-panel-head">
                            <span class="auth-panel-kicker">Step 1</span>
                            <h3 class="auth-panel-title">Send a Reset Link</h3>
                        </div>

                        <div id="forgotSuccess">
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif
                        </div>

                        <div class="auth-helper-copy mb-4">
                            Enter the email address you use to sign in. We will send your next-step link there.
                        </div>

                        <form name="forgotForm" id="forgotForm" method="POST" action="{{ route('user.password.forgot.post', [], false) }}" class="contact-form auth-form" novalidate>
                            @csrf

                            <div class="control-group mb-4 auth-field">
                                <label class="auth-label" for="forgotEmail">Email Address</label>
                                <input
                                    type="email"
                                    class="form-control auth-input"
                                    name="email"
                                    id="forgotEmail"
                                    placeholder="you@example.com"
                                    value="{{ old('email') }}"
                                    required
                                />
                                <p class="help-block text-danger auth-error" data-error-for="email">@error('email'){{ $message }}@enderror</p>
                            </div>

                            <div>
                                <button
                                    class="btn btn-primary py-3 px-4 w-100 auth-submit-btn"
                                    type="submit"
                                    id="forgotButton">
                                    Send Reset Link
                                </button>
                            </div>
                        </form>

                        <div class="auth-note-card">
                            <i class="fa fa-info-circle"></i>
                            <span>If the email does not appear right away, check spam, updates, and promotions before requesting another link.</span>
                        </div>

                        <div class="auth-link-row">
                            <a href="{{ route('user.login', [], false) }}">Back to login</a>
                            <a href="{{ route('user.register', [], false) }}">Create an account</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
