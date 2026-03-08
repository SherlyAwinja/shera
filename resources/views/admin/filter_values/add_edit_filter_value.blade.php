@extends('admin.layout.layout')

@section('content')
<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">{{ $title }} - {{ $filter->filter_name }}</h3>
                </div>

                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ route('filters.index') }}">Filters</a>
                        </li>

                        <li class="breadcrumb-item">
                            <a href="{{ route('filter-values.index', $filter->id) }}">
                                Filter Values
                            </a>
                        </li>

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


                        @foreach($errors->all() as $error)
                            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                                <strong>Error!</strong> {{ $error }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endforeach


                        <form action="{{ isset($filterValue) ? route('filter-values.update', [$filter->id, $filterValue->id]) : route('filter-values.store', $filter->id) }}" method="POST">

                            @csrf
                            @if(isset($filterValue))
                                @method('PUT')
                            @endif


                            <div class="card-body">

                                <div class="mb-3">
                                    <label for="value" class="form-label">Value*</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="value"
                                        name="value"
                                        value="{{ old('value', $filterValue->value ?? '') }}"
                                        placeholder="Enter Value"
                                    >
                                </div>


                                <div class="mb-3">
                                    <label for="sort" class="form-label">Sort Order</label>
                                    <input
                                        type="number"
                                        class="form-control"
                                        id="sort"
                                        name="sort"
                                        value="{{ old('sort', $filterValue->sort ?? 0) }}"
                                    >
                                </div>


                                <div class="mb-3">
                                    <label for="status" class="form-label">Status*</label>

                                    <select name="status" class="form-select">

                                        <option value="1"
                                            {{ old('status', $filterValue->status ?? 1) == 1 ? 'selected' : '' }}>
                                            Active
                                        </option>

                                        <option value="0"
                                            {{ old('status', $filterValue->status ?? 1) == 0 ? 'selected' : '' }}>
                                            Inactive
                                        </option>

                                    </select>
                                </div>

                            </div>


                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    Submit
                                </button>
                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

</main>
@endsection
