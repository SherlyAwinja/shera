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
                        <li class="breadcrumb-item active">{{ $title }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>


    <div class="app-content">
        <div class="container-fluid">

            @if(Session::has('success_message'))
                <div class="alert alert-success alert-dismissible fade show">
                    <strong>Success:</strong> {{ Session::get('success_message') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif


            <div class="card">

                <div class="card-header">
                    <h3 class="card-title">{{ $title }}</h3>

                    @if($filtersModule['edit_access'] == 1 || $filtersModule['full_access'] == 1)
                        <a href="{{ route('filters.create') }}" class="btn btn-primary float-end">
                            Add Filter
                        </a>
                    @endif
                </div>


                <div class="card-body">

                    <table id="filters" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Filter Name</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($filters as $filter)

                                <tr>

                                    <td>{{ $filter->id }}</td>

                                    <td>{{ $filter->filter_name }}</td>

                                    <td>
                                        @if($filter->categories->count() > 0)
                                            {{ $filter->categories->pluck('name')->join(', ') }}
                                        @else
                                            -
                                        @endif
                                    </td>


                                    <td>
                                        @if($filtersModule['edit_access'] == 1 || $filtersModule['full_access'] == 1)

                                            @if($filter->status == 1)

                                                <a class="updateFilterStatus"
                                                   data-filter-id="{{ $filter->id }}"
                                                   style="color:#3f6ed3"
                                                   href="javascript:void(0)">

                                                    <i class="fas fa-toggle-on" data-status="Active"></i>

                                                </a>

                                            @else

                                                <a class="updateFilterStatus"
                                                   data-filter-id="{{ $filter->id }}"
                                                   style="color:grey"
                                                   href="javascript:void(0)">

                                                    <i class="fas fa-toggle-off" data-status="Inactive"></i>

                                                </a>

                                            @endif

                                        @else

                                            {{ $filter->status == 1 ? 'Active' : 'Inactive' }}

                                        @endif
                                    </td>


                                    <td>

                                        @if($filtersModule['edit_access'] == 1 || $filtersModule['full_access'] == 1)

                                            <a href="{{ route('filters.edit', $filter->id) }}">
                                                <i class="fas fa-edit"></i>
                                            </a>

                                        @endif


                                        @if($filtersModule['full_access'] == 1)

                                            <form action="{{ route('filters.destroy', $filter->id) }}"
                                                  method="POST"
                                                  style="display:inline-block">

                                                @csrf
                                                @method('DELETE')

                                                <button type="button"
                                                        class="confirmDelete"
                                                        data-module="filter"
                                                        data-id="{{ $filter->id }}"
                                                        style="border:none;background:none;color:red">

                                                    <i class="fas fa-trash"></i>

                                                </button>

                                            </form>

                                        @endif


                                        @if(
                                            $filtersModule['view_access'] == 1 ||
                                            $filtersModule['edit_access'] == 1 ||
                                            $filtersModule['full_access'] == 1
                                        )

                                            <a href="{{ route('filter-values.index', $filter->id) }}"
                                               class="btn btn-sm btn-secondary">

                                                Manage Values

                                            </a>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

        </div>
    </div>

</main>
@endsection
