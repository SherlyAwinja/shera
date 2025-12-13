@extends('admin.layout.layout')
@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Catalogue Management</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Categories
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Categories</h3>
                            @if($categoriesModule['edit_access'] == 1 || $categoriesModule['full_access'] == 1)
                            <a style="max-width: 150px; float:right; display:inline-block;"
                            href="{{ url('admin/categories/create') }}" class="btn btn-primary">Add Category</a>
                            @endif
                        </div>
                        <div class="card-body">
                            @if (Session::has('success_message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Success!</strong> {{ Session::get('success_message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            @endif
                            <table id="categories" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Parent Category</th>
                                        <th>URL</th>
                                        <th>Created On</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($categories as $category)
                                        <tr>
                                            <td>{{ $category->id }}</td>
                                            <td>{{ $category->name }}</td>
                                            <td>{{ $category->parentcategory->name ?? '' }}</td>
                                            <td>{{ $category->url }}</td>
                                            <td>{{ $category->created_at->format('F j, Y, g:i a') }}</td>
                                            <td>
                                                <!-- Actions (Enable/Disable, Edit, Delete) will be added here -->
                                                    @if($category->status == 1)
                                                    <a class="updateCategoryStatus" data-category_id="{{ $category->id }}" style="color:#3f6ed3;" href="javascript:void(0)"><i class="fas fa-toggle-on" data-status="Active"></i></a>
                                                    @else
                                                    <a class="updateCategoryStatus" data-category_id="{{ $category->id }}" style="color:grey;" href="javascript:void(0)"><i class="fas fa-toggle-off" data-status="Inactive"></i></a>
                                                    @endif
                                                &nbsp;&nbsp;
                                                <a href="{{ url('admin/categories/'.$category->id.'/edit') }}"><i class="fas fa-edit"></i></a>
                                                &nbsp;&nbsp;
                                                <form action="{{ route('categories.destroy', $category->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" style="border:none; background:none;color:#3f6ed3;" title="Delete Category"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
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