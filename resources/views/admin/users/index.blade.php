@extends('admin.layout.layout')

@section('content')
<main class="app-main">

    <!-- Header -->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Users Management</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Users</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">

                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Users</h3>

                            @if(isset($usersModule) && ($usersModule['edit_access'] == 1 || $usersModule['full_access'] == 1))
                                <a href="{{ url('admin/users/create') }}" class="btn btn-primary" style="max-width: 200px;">
                                    Add User
                                </a>
                            @endif
                        </div>

                        <div class="card-body">

                            <!-- Success Message -->
                            @if(Session::has('success_message'))
                                <div class="alert alert-success alert-dismissible fade show mx-1 my-3" role="alert">
                                    <strong>Success: </strong> {{ Session::get('success_message') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table id="users" class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Address Line 1</th>
                                            <th>Address Line 2</th>
                                            <th>County</th>
                                            <th>Sub-County</th>
                                            <th>Estate</th>
                                            <th>Landmark</th>
                                            <th>Country</th>
                                            <th>Wallet Balance</th>
                                            <th>Registered On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($users as $user)
                                            <tr>
                                                <td>{{ $user->id }}</td>
                                                <td>{{ $user->name ?? '-' }}</td>
                                                <td>{{ $user->email }}</td>
                                                <td>{{ $user->address_line1 ?? '-' }}</td>
                                                <td>{{ $user->address_line2 ?? '-' }}</td>
                                                <td>{{ $user->county ?? '-' }}</td>
                                                <td>{{ $user->sub_county ?? '-' }}</td>
                                                <td>{{ $user->estate ?? '-' }}</td>
                                                <td>{{ $user->landmark ?? '-' }}</td>
                                                <td>{{ $user->country ?? '-' }}</td>
                                                <td>KES {{ number_format((float) ($user->wallet_balance ?? 0), 2) }}</td>
                                                <td>{{ optional($user->created_at)->format('F j, Y, g:i a') }}</td>
                                                <td>

                                                    <!-- Toggle Active/Inactive -->
                                                    @if(isset($usersModule) && ($usersModule['edit_access'] == 1 || $usersModule['full_access'] == 1))
                                                        @if($user->status == 1)
                                                            <a class="updateUserStatus" data-user-id="{{ $user->id }}" href="javascript:void(0)" style="color:#3f6ed3;">
                                                                <i class="fas fa-toggle-on" data-status="Active"></i>
                                                            </a>
                                                        @else
                                                            <a class="updateUserStatus" data-user-id="{{ $user->id }}" href="javascript:void(0)" style="color:grey;">
                                                                <i class="fas fa-toggle-off" data-status="Inactive"></i>
                                                            </a>
                                                        @endif
                                                    @endif

                                                    <!-- Edit -->
                                                    @if(isset($usersModule) && ($usersModule['edit_access'] == 1 || $usersModule['full_access'] == 1))
                                                        &nbsp;&nbsp;
                                                        <a href="{{ url('admin/users/'.$user->id.'/edit') }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endif

                                                    <!-- Delete -->
                                                    @if(isset($usersModule) && $usersModule['full_access'] == 1)
                                                        &nbsp;&nbsp;
                                                        <form action="{{ url('admin/users/'.$user->id) }}" method="POST" style="display:inline-block;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="button" class="confirmDelete" data-module="user" data-id="{{ $user->id }}" title="Delete User" style="border:none; background:none; color:#3f6ed3;">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif

                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="mt-3">
                                @if(method_exists($users, 'links'))
                                    {{ $users->links() }}
                                @endif
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

</main>
@endsection
