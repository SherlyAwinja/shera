@extends('admin.layout.layout')

@section('content')
<main class="app-main">

    <!-- Header -->
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Coupons Management</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Coupons</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="app-content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">

                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Coupons</h3>

                            @if($couponsModule['edit_access'] == 1 || $couponsModule['full_access'] == 1)
                                <a href="{{ route('coupons.create') }}" class="btn btn-primary float-end">
                                    Add Coupon
                                </a>
                            @endif
                        </div>

                        <div class="card-body">

                            <!-- Success Message -->
                            @if(Session::has('success_message'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <strong>Success:</strong> {{ Session::get('success_message') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <!-- Table -->
                            <table id="coupons" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Coupon Type</th>
                                        <th>Amount</th>
                                        <th>Expiry Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($coupons as $coupon)
                                        <tr>
                                            <td>{{ $coupon->coupon_code }}</td>

                                            <td>{{ ucfirst($coupon->coupon_type) }}</td>

                                            <td>
                                                {{ $coupon->amount }}
                                                @if(strtolower($coupon->amount_type) === 'percentage')
                                                    %
                                                @else
                                                    KES
                                                @endif
                                            </td>

                                            <td>
                                                @if($coupon->expiry_date)
                                                    {{ \Carbon\Carbon::parse($coupon->expiry_date)->format('F j, Y') }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>

                                            <!-- Status -->
                                            <td>
                                                <span class="coupon-status-badge me-2">
                                                    {{ $coupon->status ? 'Active' : 'Inactive' }}
                                                </span>

                                                @if($couponsModule['edit_access'] == 1 || $couponsModule['full_access'] == 1)
                                                    <a class="updateCouponStatus"
                                                       id="coupon-{{ $coupon->id }}"
                                                       data-coupon_id="{{ $coupon->id }}"
                                                       title="{{ $coupon->status ? 'Disable Coupon' : 'Enable Coupon' }}"
                                                       href="javascript:void(0)">

                                                        <i class="fas fa-toggle-{{ $coupon->status ? 'on' : 'off' }}"
                                                           data-status="{{ $coupon->status ? 'Active' : 'Inactive' }}"
                                                           style="color: {{ $coupon->status ? '#3f6ed3' : 'grey' }}"></i>
                                                    </a>
                                                @else
                                                    <i class="fas fa-toggle-{{ $coupon->status ? 'on' : 'off' }}"
                                                       style="color: {{ $coupon->status ? '#3f6ed3' : 'grey' }}"></i>
                                                @endif
                                            </td>

                                            <!-- Actions -->
                                            <td>
                                                @if($couponsModule['edit_access'] == 1 || $couponsModule['full_access'] == 1)
                                                    <a href="{{ route('coupons.edit', $coupon->id) }}" title="Edit Coupon">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    &nbsp;
                                                @endif

                                                @if($couponsModule['full_access'] == 1)
                                                    <form action="{{ route('coupons.destroy', $coupon->id) }}"
                                                          method="POST"
                                                          style="display:inline-block;">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit"
                                                                class="confirmDelete"
                                                                data-module="coupon"
                                                                data-id="{{ $coupon->id }}"
                                                                style="border:none; background:none; color:#3f6ed3;">
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
                    </div>

                </div>
            </div>
        </div>
    </div>

</main>
@endsection
