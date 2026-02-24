
@extends('admin.layout.layout')

@section('content')

<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Banner Management</h3>
                </div>

                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">
                            {{ $title }}
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card card-primary card-outline mb-4">
                        <div class="card-header">
                            <div class="card-title">{{ $title }}</div>
                        </div>

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

                        <form action="{{ isset($banner->id) ? route('banners.update', $banner->id) : route('banners.store') }}"
                              method="POST"
                              enctype="multipart/form-data">

                            @csrf

                            @if(isset($banner->id))
                                @method('PUT')
                            @endif

                            <div class="card-body">

                                <div class="mb-3">
                                    <label for="type">Banner Type*</label>
                                    <select class="form-control" name="type" id="type">
                                        <option value="">Select Type</option>
                                        <option value="Slider" {{ old('type', $banner->type ?? '') == 'Slider' ? 'selected' : '' }}>
                                            Slider
                                        </option>
                                        <option value="Fix" {{ old('type', $banner->type ?? '') == 'Fix' ? 'selected' : '' }}>
                                            Fix
                                        </option>
                                        <option value="Logo" {{ old('type', $banner->type ?? '') == 'Logo' ? 'selected' : '' }}>
                                            Logo
                                        </option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="title">Banner Title*</label>
                                    <input type="text" name="title" id="title" class="form-control"
                                           value="{{ old('title', $banner->title ?? '') }}">
                                </div>

                                <div class="mb-3">
                                    <label for="alt">Alt Text</label>
                                    <input type="text" name="alt" id="alt" class="form-control"
                                           value="{{ old('alt', $banner->alt ?? '') }}">
                                </div>

                                <div class="mb-3">
                                    <label for="link">Banner Link</label>
                                    <input type="text" name="link" id="link" class="form-control"
                                           value="{{ old('link', $banner->link ?? '') }}">
                                </div>

                                <div class="mb-3">
                                    <label for="sort">Sort Order</label>
                                    <input type="number" name="sort" id="sort" class="form-control"
                                           value="{{ old('sort', $banner->sort ?? 0) }}">
                                </div>

                                <div class="mb-3">
                                    <label for="image">Banner Image @if(!isset($banner->id)) * @endif</label>
                                    <input type="file" name="image" id="image" class="form-control">

                                    @if(!empty($banner->image))
                                        <div class="mt-2">
                                            <img src="{{ asset('front/images/banners/' . $banner->image) }}" width="100" alt="Banner">
                                        </div>
                                    @endif
                                </div>

                                <div class="mb-3">
                                    <label>Status</label><br>
                                    <input type="checkbox" name="status" value="1"
                                           {{ old('status', $banner->status ?? 1) == 1 ? 'checked' : '' }}>
                                    Active
                                </div>

                                <div class="card-footer">
                                    <button class="btn btn-primary" type="submit">Submit</button>
                                </div>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection



