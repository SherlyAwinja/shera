@extends('admin.layout.layout')

@section('content')
<main class="app-main">

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">

                <div class="col-sm-6">
                    <h3 class="mb-0">Manage Filter Values - {{ $filter->filter_name }}</h3>
                </div>

                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item">
                            <a href="{{ route('filters.index') }}">Filters</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Filter Values
                        </li>
                    </ol>
                </div>

            </div>
        </div>
    </div>


    <div class="app-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-md-12">

                    <div class="card mb-4">

                        <div class="card-header">
                            <h3 class="card-title">Filter Values</h3>

                            {{-- Show Add button only if allowed --}}
                            @if(($filtersValuesModule['full_access'] ?? false) || ($filtersValuesModule['edit_access'] ?? false))
                                <a href="{{ route('filter-values.create', $filter->id) }}"
                                   class="btn btn-primary"
                                   style="max-width:200px; float:right;">
                                    Add Filter Value
                                </a>
                            @endif
                        </div>


                        <div class="card-body">

                            @if(Session::has('success_message'))
                                <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                                    <strong>Success:</strong> {{ Session::get('success_message') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif


                            <table id="filters_values" class="table table-bordered table-striped">

                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Value</th>
                                        <th>Sort</th>
                                        <th>Status</th>

                                        @if(($filtersValuesModule['edit_access'] ?? false) || ($filtersValuesModule['full_access'] ?? false))
                                            <th>Actions</th>
                                        @endif
                                    </tr>
                                </thead>


                                <tbody>

                                    @foreach($filterValues as $value)

                                        <tr>

                                            <td>{{ $value->id }}</td>

                                            <td>{{ ucfirst($value->value) }}</td>

                                            <td>{{ $value->sort }}</td>

                                            <td>
                                                @if($value->status == 1)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>


                                            @if(($filtersValuesModule['edit_access'] ?? false) || ($filtersValuesModule['full_access'] ?? false))

                                                <td>

                                                    {{-- Edit --}}
                                                    @if(($filtersValuesModule['edit_access'] ?? false) || ($filtersValuesModule['full_access'] ?? false))
                                                        <a href="{{ route('filter-values.edit', [$filter->id, $value->id]) }}"
                                                           class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endif


                                                    {{-- Delete --}}
                                                    @if($filtersValuesModule['full_access'] ?? false)

                                                        <form action="{{ route('filter-values.destroy', [$filter->id, $value->id]) }}"
                                                              method="POST"
                                                              style="display:inline-block">

                                                            @csrf
                                                            @method('DELETE')

                                                            <button type="submit"
                                                                    class="btn btn-sm btn-danger confirmDelete"
                                                                    name="Filter Value">

                                                                <i class="fas fa-trash"></i>

                                                            </button>

                                                        </form>

                                                    @endif

                                                </td>

                                            @endif

                                        </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>

</main>
@endsection
