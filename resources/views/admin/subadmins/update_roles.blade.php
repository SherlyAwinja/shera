@extends('admin.layout.layout')
@section('content')
<!--begin::App Main-->
<main class="app-main">
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
                        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
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
        <div class="container-fluid">
            <!--begin::Row-->
            <div class="row g-4">
                <!--begin::Col-->
                <div class="col-md-12">
                    <!--begin::Quick Example-->
                    <div class="card card-primary card-outline mb-4">
                        <!--begin::Header-->
                        <div class="card-header">
                            <div class="card-title">{{ $title }}</div>
                        </div>
                        <!--end::Header-->
                        @if (Session::has('error_message'))
                            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                                <strong>Error!</strong> {{ Session::get('error_message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        @if (Session::has('success_message'))
                            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                <strong>Success!</strong> {{ Session::get('success_message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        @foreach ($errors->all() as $error)
                            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                                <strong>Error!</strong> {{ $error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endforeach
                        <!--begin::Form-->
                        <form name="subadminForm" id="subadminForm" action="{{ url('admin/update-role/request/') }}" method="post">@csrf
                            <input type="hidden" name="subadmin_id" value="{{ $id }}">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    @foreach($modules as $module)
                                    @php
                                        // Fetch Permissions for current module
                                        $viewAccess = $editAccess = $fullAccess = "";
                                        foreach($subadminRoles as $role) {
                                            if($role['module'] == $module) {
                                                $viewAccess = $role['view_access'] == 1 ? 'checked' : "";
                                                $editAccess = $role['edit_access'] == 1 ? 'checked' : "";
                                                $fullAccess = $role['full_access'] == 1 ? 'checked' : "";
                                                break;
                                            }
                                        }
                                    @endphp
                                    <div class="form-group mb-3">
                                        <label><strong>{{ ucwords(str_replace('_', ' ', $module)) }}:&nbsp;&nbsp;&nbsp;</strong></label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="{{ $module }}[view]" value="1" {{ $viewAccess }}>
                                            <label class="form-check-label">View Access</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="{{ $module }}[edit]" value="1" {{ $editAccess }}>
                                            <label class="form-check-label">Edit/View Access</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="{{ $module }}[full]" value="1" {{ $fullAccess }}>
                                            <label class="form-check-label">Full Access</label>
                                        </div>
                                    </div>

                                    <hr>
                                    @endforeach
                                </div>
                                <div class="card-footer text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                        <!--end::Form-->
                    </div>
                    <!--end::Quick Example-->
                </div>
                <!--end::Col-->
            </div>
            <!--end::Row-->
        </div>
        <!--end::Container-->
    </div>
    <!--end::App Content-->
</main>
<!--end::App Main-->
@endsection