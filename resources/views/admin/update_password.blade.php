@extends('admin.layout.layout')
@section('content')
<style>
    .update-password-page .security-shell {
        background:
            radial-gradient(circle at 5% 5%, rgba(13, 110, 253, 0.08), transparent 35%),
            radial-gradient(circle at 95% 95%, rgba(25, 135, 84, 0.08), transparent 40%);
        border-radius: 1rem;
        padding: 0.5rem;
    }

    .update-password-page .security-card,
    .update-password-page .rules-card {
        border: 0;
        border-radius: 0.9rem;
        box-shadow: 0 12px 30px rgba(23, 46, 86, 0.12);
        overflow: hidden;
    }

    .update-password-page .security-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0aa2c0 100%);
        color: #fff;
        border-bottom: 0;
        padding: 0.9rem 1rem;
    }

    .update-password-page .security-badge {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.35);
        font-weight: 600;
    }

    .update-password-page .rules-header {
        background: linear-gradient(135deg, #198754 0%, #20c997 100%);
        color: #fff;
        border-bottom: 0;
        padding: 0.9rem 1rem;
    }

    .update-password-page .security-addon {
        background: #eef4ff;
        border-color: #d5e3ff;
        color: #3b5ca8;
    }

    .update-password-page .security-input {
        border-color: #d7e1f0;
    }

    .update-password-page .security-input:focus {
        border-color: #6ea0ff;
        box-shadow: 0 0 0 0.18rem rgba(13, 110, 253, 0.18);
    }

    .update-password-page .security-note {
        border-left: 3px solid #0d6efd;
        background: #f4f8ff;
        color: #4b5d7a;
        border-radius: 0.4rem;
        padding: 0.6rem 0.75rem;
        font-size: 0.86rem;
    }

    .update-password-page .security-footer {
        background: #fafcff;
        border-top: 1px solid #edf2f9;
    }

    .update-password-page .security-submit {
        border-radius: 0.55rem;
    }

    .update-password-page .rule-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }

    .update-password-page .rule-list li {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        margin-bottom: 0.6rem;
        color: #3f4f69;
    }

    .update-password-page .rule-list li:last-child {
        margin-bottom: 0;
    }

    .update-password-page #verifyPassword {
        min-height: 1.1rem;
    }
</style>
<!--begin::App Main-->
<main class="app-main update-password-page">
    <!--begin::App Content Header-->
    <div class="app-content-header">
        <!--begin::Container-->
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Admin Management</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Update Password</li>
                    </ol>
                </div>
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content Header-->
    <!--begin::App Content-->
    <div class="app-content">
        <!--begin::Container-->
        <div class="container-fluid security-shell">
            <!--begin::Row-->
            <div class="row g-4 align-items-start">
                <!--begin::Col-->
                <div class="col-lg-8 col-xl-7">
                    <!--begin::Quick Example-->
                    <div class="card card-primary card-outline mb-4 security-card">
                        <!--begin::Header-->
                        <div class="card-header d-flex align-items-center justify-content-between security-header">
                            <div class="card-title mb-0"><i class="bi bi-shield-lock-fill me-2"></i>Update Password</div>
                            <span class="badge security-badge">Security</span>
                        </div>
                        <!--end::Header-->
                        @if (Session::has('error_message'))
                            <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3 mb-0" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Error!</strong> {{ Session::get('error_message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        @if (Session::has('success_message'))
                            <div class="alert alert-success alert-dismissible fade show mx-3 mt-3 mb-0" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Success!</strong> {{ Session::get('success_message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        @foreach ($errors->all() as $error)
                            <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3 mb-0" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <strong>Error!</strong> {{ $error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endforeach
                        <!--begin::Form-->
                        <form method="post" action="{{ route('admin.update-password.request') }}">@csrf
                            <!--begin::Body-->
                            <div class="card-body">
                                <div class="mb-4">
                                    <label for="email" class="form-label"><i class="bi bi-envelope-fill text-primary me-1"></i>Email address</label>
                                    <div class="input-group">
                                        <span class="input-group-text security-addon"><i class="bi bi-person-badge"></i></span>
                                        <input
                                            type="email"
                                            class="form-control bg-light security-input"
                                            id="email"
                                            aria-describedby="emailHelp"
                                            value="{{ Auth::guard('admin')->user()->email }}"
                                            readonly
                                        />
                                    </div>
                                    <div id="emailHelp" class="form-text">
                                        This account email cannot be changed here.
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="current_password" class="form-label"><i class="bi bi-key-fill text-primary me-1"></i>Current Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text security-addon"><i class="bi bi-lock-fill"></i></span>
                                        <input
                                            type="password"
                                            class="form-control security-input"
                                            id="current_password"
                                            name="current_password"
                                            autocomplete="current-password"
                                            required
                                        />
                                    </div>
                                    <span id="verifyPassword" class="d-block mt-1"></span>
                                </div>
                                <div class="mb-4">
                                    <label for="new_password" class="form-label"><i class="bi bi-shield-lock-fill text-primary me-1"></i>New Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text security-addon"><i class="bi bi-key-fill"></i></span>
                                        <input
                                            type="password"
                                            class="form-control security-input"
                                            id="new_password"
                                            name="new_password"
                                            autocomplete="new-password"
                                            required
                                        />
                                    </div>
                                    <div class="form-text">Use at least 8 characters.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label"><i class="bi bi-check-circle-fill text-primary me-1"></i>Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text security-addon"><i class="bi bi-lock-fill"></i></span>
                                        <input
                                            type="password"
                                            class="form-control security-input"
                                            id="confirm_password"
                                            name="confirm_password"
                                            autocomplete="new-password"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="security-note mt-4">
                                    <i class="bi bi-info-circle-fill me-1 text-primary"></i>
                                    Keep this password unique and avoid sharing it with anyone.
                                </div>
                            </div>
                            <!--end::Body-->
                            <!--begin::Footer-->
                            <div class="card-footer d-flex justify-content-between align-items-center security-footer">
                                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>You may need to login again after password update.</small>
                                <button type="submit" class="btn btn-primary px-4 security-submit">
                                    <i class="bi bi-check2-circle me-1"></i>Update Password
                                </button>
                            </div>
                            <!--end::Footer-->
                        </form>
                        <!--end::Form-->
                    </div>
                    <!--end::Quick Example-->
                </div>
                <!--end::Col-->
                <div class="col-lg-4 col-xl-5">
                    <div class="card mb-4 rules-card">
                        <div class="card-header rules-header">
                            <div class="card-title mb-0"><i class="bi bi-list-check me-2"></i>Password Rules</div>
                        </div>
                        <div class="card-body">
                            <div class="small text-muted mb-2">Recommended for stronger account security:</div>
                            <ul class="rule-list">
                                <li><i class="bi bi-check-circle-fill text-success mt-1"></i><span>Minimum 8 characters.</span></li>
                                <li><i class="bi bi-check-circle-fill text-success mt-1"></i><span>Mix uppercase and lowercase letters.</span></li>
                                <li><i class="bi bi-check-circle-fill text-success mt-1"></i><span>Add numbers and at least one symbol.</span></li>
                                <li><i class="bi bi-check-circle-fill text-success mt-1"></i><span>Avoid reusing old passwords.</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content-->
</main>
<!--end::App Main-->
@endsection
