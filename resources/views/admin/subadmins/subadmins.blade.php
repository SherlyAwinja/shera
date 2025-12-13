@extends('admin.layout.layout')
@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Admin Management</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Subadmins
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
                            <h3 class="card-title">Subadmins</h3>
                            <a style="max-width: 150px; float:right; display:inline-block;"
                            href="{{ url('admin/add-edit-subadmin') }}" class="btn btn-primary">Add Subadmin</a>
                            </div>
                        </div>
                        <div class="card-body">
                            @if (Session::has('success_message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <strong>Success!</strong> {{ Session::get('success_message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            @endif
                            <table id="subadmins" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subadmins as $subadmin)
                                        <tr>
                                            <td>{{ $subadmin->id }}</td>
                                            <td>{{ $subadmin->name }}</td>
                                            <td>{{ $subadmin->email }}</td>
                                            <td>{{ $subadmin->mobile }}</td>
                                            <td>
                                                @if ($subadmin->status == 1)
                                                <a class="updateSubadminStatus" 
                                                data-subadmin_id="{{ $subadmin->id }}" style='color:#3f6ed3' href="javascript:void(0)"><i class="fas fa-toggle-on" 
                                                data-status="Active"></i></a>
                                                @else
                                                <a class="updateSubadminStatus" data-subadmin_id="{{ $subadmin->id }}" style='color:gray' href="javascript:void(0)"><i class="fas fa-toggle-off" data-status="Inactive"></i></a>
                                                @endif
                                                &nbsp;&nbsp;
                                                <a href="{{ url('admin/add-edit-subadmin') }}/{{ $subadmin->id }}"><i class="fas fa-edit"></i></a>
                                                &nbsp;&nbsp;
                                                <a title="Set Permissions for Sub-admin" href="{{ url('admin/update-role/'.$subadmin->id) }}"><i class="fas fa-unlock"></i></a>
                                                &nbsp;&nbsp;
                                                <a class="confirmDelete" name="Subadmin" style="color:#3f6ed3" title="Delete Subadmin" data-module="subadmin" data-id="{{ $subadmin->id }}" <?php /* href="{{ url('admin/delete-subadmin/') }}/{{ $subadmin->id }}" */?>><i class="fas fa-trash"></i></a>
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