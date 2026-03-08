@extends('admin.layout.layout')
@section('content')
<style>
    .update-details-page .details-shell {
        background:
            radial-gradient(circle at 8% 8%, rgba(13, 110, 253, 0.08), transparent 35%),
            radial-gradient(circle at 92% 90%, rgba(25, 135, 84, 0.08), transparent 40%);
        border-radius: 1rem;
        padding: 0.5rem;
    }

    .update-details-page .details-card,
    .update-details-page .tips-card {
        border: 0;
        border-radius: 0.9rem;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(23, 46, 86, 0.12);
    }

    .update-details-page .details-header {
        background: linear-gradient(135deg, #0d6efd 0%, #0aa2c0 100%);
        color: #fff;
        border-bottom: 0;
        padding: 0.9rem 1rem;
    }

    .update-details-page .tips-header {
        background: linear-gradient(135deg, #198754 0%, #20c997 100%);
        color: #fff;
        border-bottom: 0;
        padding: 0.9rem 1rem;
    }

    .update-details-page .details-badge {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.35);
        font-weight: 600;
    }

    .update-details-page .details-addon {
        background: #eef4ff;
        border-color: #d5e3ff;
        color: #3b5ca8;
    }

    .update-details-page .details-input {
        border-color: #d7e1f0;
    }

    .update-details-page .details-input:focus {
        border-color: #6ea0ff;
        box-shadow: 0 0 0 0.18rem rgba(13, 110, 253, 0.18);
    }

    .update-details-page .profile-box {
        border: 1px solid #e5edf9;
        border-radius: 0.7rem;
        background: #f9fbff;
        padding: 0.75rem;
    }

    .update-details-page .profile-preview {
        width: 56px;
        height: 56px;
        object-fit: cover;
        border-radius: 50%;
        border: 2px solid #d7e6ff;
    }

    .update-details-page .details-footer {
        background: #fafcff;
        border-top: 1px solid #edf2f9;
    }

    .update-details-page .tips-list {
        list-style: none;
        padding-left: 0;
        margin-bottom: 0;
    }

    .update-details-page .tips-list li {
        display: flex;
        gap: 0.5rem;
        align-items: flex-start;
        margin-bottom: 0.6rem;
        color: #3f4f69;
    }

    .update-details-page .tips-list li:last-child {
        margin-bottom: 0;
    }
</style>
<!--begin::App Main-->
<main class="app-main update-details-page">
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
                        <li class="breadcrumb-item active" aria-current="page">Update Details</li>
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
        <div class="container-fluid details-shell">
            <!--begin::Row-->
            <div class="row g-4 align-items-start">
                <!--begin::Col-->
                <div class="col-lg-8 col-xl-7">
                    <!--begin::Quick Example-->
                    <div class="card card-primary card-outline mb-4 details-card">
                        <!--begin::Header-->
                        <div class="card-header d-flex align-items-center justify-content-between details-header">
                            <div class="card-title mb-0"><i class="bi bi-person-vcard-fill me-2"></i>Update Details</div>
                            <span class="badge details-badge">Profile</span>
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
                        <form method="post" action="{{ route('admin.update-details.request') }}" enctype="multipart/form-data">@csrf
                            <!--begin::Body-->
                            <div class="card-body">
                                <div class="mb-4">
                                    <label for="email" class="form-label"><i class="bi bi-envelope-fill text-primary me-1"></i>Email address</label>
                                    <div class="input-group">
                                        <span class="input-group-text details-addon"><i class="bi bi-person-badge"></i></span>
                                        <input
                                            type="email"
                                            class="form-control bg-light details-input"
                                            id="email"
                                            value="{{ Auth::guard('admin')->user()->email }}"
                                            readonly
                                        />
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="name" class="form-label"><i class="bi bi-person-fill text-primary me-1"></i>Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text details-addon"><i class="bi bi-card-text"></i></span>
                                        <input
                                            type="text"
                                            class="form-control details-input"
                                            id="name"
                                            name="name"
                                            value="{{ old('name', Auth::guard('admin')->user()->name) }}"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="mobile" class="form-label"><i class="bi bi-telephone-fill text-primary me-1"></i>Mobile</label>
                                    <div class="input-group">
                                        <span class="input-group-text details-addon"><i class="bi bi-phone"></i></span>
                                        <input
                                            type="text"
                                            class="form-control details-input"
                                            id="mobile"
                                            name="mobile"
                                            value="{{ old('mobile', Auth::guard('admin')->user()->mobile) }}"
                                            required
                                        />
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="image" class="form-label"><i class="bi bi-image-fill text-primary me-1"></i>Profile Image</label>
                                    <input type="file" class="form-control details-input" id="image" name="image" accept="image/*"/>
                                    @if(!empty(Auth::guard('admin')->user()->image))
                                        <div id="profileImageBlock" class="profile-box mt-3">
                                            <div class="d-flex align-items-center justify-content-between gap-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img
                                                        class="profile-preview"
                                                        src="{{ url('admin/images/photos/' . Auth::guard('admin')->user()->image) }}"
                                                        alt="Profile Image"
                                                    />
                                                    <div>
                                                        <div class="fw-semibold small">Current profile image</div>
                                                        <a target="_blank" href="{{ url('admin/images/photos/' . Auth::guard('admin')->user()->image) }}" class="small">View full image</a>
                                                    </div>
                                                </div>
                                                <a href="javascript:void(0)" id="deleteProfileImage" data-admin-id="{{ Auth::guard('admin')->user()->id }}" class="text-danger small">
                                                    <i class="bi bi-trash3-fill me-1"></i>Delete
                                                </a>
                                            </div>
                                            <input type="hidden" name="current_image" value="{{ Auth::guard('admin')->user()->image }}" />
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <!--end::Body-->
                            <!--begin::Footer-->
                            <div class="card-footer d-flex justify-content-between align-items-center details-footer">
                                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Changes apply immediately after save.</small>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-check2-circle me-1"></i>Update Details
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
                    <div class="card mb-4 tips-card">
                        <div class="card-header tips-header">
                            <div class="card-title mb-0"><i class="bi bi-stars me-2"></i>Profile Tips</div>
                        </div>
                        <div class="card-body">
                            <ul class="tips-list">
                                <li><i class="bi bi-check-circle-fill text-success mt-1"></i><span>Use your full name for easier team identification.</span></li>
                                <li><i class="bi bi-check-circle-fill text-success mt-1"></i><span>Keep mobile number up to date for account recovery.</span></li>
                                <li><i class="bi bi-check-circle-fill text-success mt-1"></i><span>Upload a clear square profile image for best display.</span></li>
                                <li><i class="bi bi-check-circle-fill text-success mt-1"></i><span>Save details before leaving this page.</span></li>
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
