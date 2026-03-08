@extends('admin.layout.layout')

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">{{ $title }}</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
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

                        @if(Session::has('error_message'))
                            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                                <strong>Error:</strong> {{ Session::get('error_message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @if(Session::has('success_message'))
                            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                                <strong>Success:</strong> {{ Session::get('success_message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        @foreach($errors->all() as $error)
                            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                                <strong>Error!</strong> {{ $error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endforeach

                        <form action="{{ isset($filter) ? route('filters.update', $filter->id) : route('filters.store') }}" method="POST">
                            @csrf
                            @if(isset($filter))
                                @method('PUT')
                            @endif

                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="category_ids" class="form-label">Categories*</label>
                                    <select name="category_ids[]" id="category_ids" class="form-control" multiple>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}"
                                                @if(in_array($cat->id, old('category_ids', $selectedCategories ?? []))) selected @endif>
                                                {{ $cat->name }}
                                            </option>

                                            @if(!empty($cat->subcategories))
                                                @foreach($cat->subcategories as $subcat)
                                                    <option value="{{ $subcat->id }}"
                                                        @if(in_array($subcat->id, old('category_ids', $selectedCategories ?? []))) selected @endif>
                                                        &nbsp;&nbsp;-- {{ $subcat->name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </select>
                                    <small class="text-muted">
                                        Hold CTRL (Windows) or CMD (Mac) to select multiple
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <label for="filter_name" class="form-label">Filter Name*</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="filter_name"
                                        name="filter_name"
                                        value="{{ old('filter_name', $filter->filter_name ?? '') }}"
                                        placeholder="Enter Filter Name"
                                    >
                                </div>

                                <div class="mb-3">
                                    <label for="filter_column" class="form-label">Filter Column*</label>
                                    <input
                                        type="text"
                                        name="filter_column"
                                        id="filter_column"
                                        class="form-control"
                                        value="{{ old('filter_column', $filter->filter_column ?? '') }}"
                                    >
                                </div>

                                <div class="mb-3">
                                    <label for="sort" class="form-label">Sort Order</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        id="sort"
                                        name="sort"
                                        value="{{ old('sort', $filter->sort ?? 0) }}"
                                    >
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status*</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="1" {{ old('status', $filter->status ?? 1) == 1 ? 'selected' : '' }}>
                                            Active
                                        </option>
                                        <option value="0" {{ old('status', $filter->status ?? 1) == 0 ? 'selected' : '' }}>
                                            Inactive
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
