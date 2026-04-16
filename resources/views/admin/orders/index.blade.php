@extends('admin.layout.layout')

@section('content')
@php
    $orderStatusBadgeClasses = [
        'placed' => 'text-bg-warning text-dark',
        'confirmed' => 'text-bg-info',
        'processing' => 'text-bg-primary',
        'shipped' => 'text-bg-info',
        'delivered' => 'text-bg-success',
        'completed' => 'text-bg-success',
        'cancelled' => 'text-bg-danger',
    ];
    $paymentStatusBadgeClasses = [
        'pending' => 'text-bg-secondary',
        'partially_paid' => 'text-bg-warning text-dark',
        'paid' => 'text-bg-success',
        'failed' => 'text-bg-danger',
        'refunded' => 'text-bg-dark',
    ];
@endphp

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Orders Management</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Orders</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h3 class="card-title mb-1">Customer Orders</h3>
                        <p class="text-muted mb-0 small">Search by order number, customer, contact details, payment method, or workflow status.</p>
                    </div>
                    <span class="badge text-bg-dark fs-6">Total Orders: {{ number_format($orders->total()) }}</span>
                </div>

                <div class="card-body">
                    @if(Session::has('success_message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success:</strong> {{ Session::get('success_message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(Session::has('error_message'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error:</strong> {{ Session::get('error_message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error:</strong> {{ $errors->first() }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('orders.index') }}" class="row g-3 align-items-end mb-4">
                        <div class="col-lg-4">
                            <label for="ordersSearch" class="form-label">Search</label>
                            <input
                                type="text"
                                id="ordersSearch"
                                name="q"
                                value="{{ $filters['q'] ?? '' }}"
                                class="form-control"
                                placeholder="Order number, customer, email, phone"
                            >
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="ordersStatus" class="form-label">Order Status</label>
                            <select name="order_status" id="ordersStatus" class="form-select">
                                <option value="">All statuses</option>
                                @foreach($orderStatusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ ($filters['order_status'] ?? null) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="paymentStatus" class="form-label">Payment Status</label>
                            <select name="payment_status" id="paymentStatus" class="form-select">
                                <option value="">All payments</option>
                                @foreach($paymentStatusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ ($filters['payment_status'] ?? null) === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <label for="paymentMethod" class="form-label">Method</label>
                            <select name="payment_method" id="paymentMethod" class="form-select">
                                <option value="">All methods</option>
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method }}" {{ ($filters['payment_method'] ?? null) === $method ? 'selected' : '' }}>
                                        {{ \App\Models\Order::formatOptionLabel($method) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-1">
                            <label for="perPage" class="form-label">Rows</label>
                            <select name="per_page" id="perPage" class="form-select">
                                @foreach([10, 25, 50, 100] as $size)
                                    <option value="{{ $size }}" {{ (int) ($filters['per_page'] ?? 25) === $size ? 'selected' : '' }}>
                                        {{ $size }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-outline-primary">Apply</button>
                        </div>
                        <div class="col-md-auto">
                            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Payment</th>
                                    <th>Fulfillment</th>
                                    <th>Total</th>
                                    <th>Placed On</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $order->order_number ?: 'Order #' . $order->id }}</div>
                                            <div class="small text-muted">{{ $order->order_uuid ?: 'Reference unavailable' }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $order->recipient_name ?: (optional($order->user)->name ?: 'Guest User') }}</div>
                                            <div class="small text-muted">{{ $order->email ?: (optional($order->user)->email ?: 'No email address') }}</div>
                                            @if($order->recipient_phone)
                                                <div class="small text-muted">{{ $order->recipient_phone }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $order->payment_method_label }}</div>
                                            <div class="mt-1">
                                                <span class="badge {{ $paymentStatusBadgeClasses[$order->payment_status] ?? 'text-bg-secondary' }}">
                                                    {{ $order->payment_status_label }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="badge {{ $orderStatusBadgeClasses[$order->order_status] ?? 'text-bg-secondary' }}">
                                                    {{ $order->order_status_label }}
                                                </span>
                                            </div>
                                            <div class="small text-muted mt-1">{{ number_format((int) $order->items_count) }} item{{ (int) $order->items_count === 1 ? '' : 's' }}</div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $order->currency ?? 'KES' }} {{ number_format($order->total, 2) }}</div>
                                            @if((float) $order->shipping > 0)
                                                <div class="small text-muted">Shipping {{ $order->currency ?? 'KES' }} {{ number_format($order->shipping, 2) }}</div>
                                            @endif
                                            @if((float) $order->wallet_applied_amount > 0)
                                                <div class="small text-muted">Wallet {{ $order->currency ?? 'KES' }} {{ number_format((float) $order->wallet_applied_amount, 2) }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ optional($order->placed_at ?? $order->created_at)->format('M j, Y g:i a') }}
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            No orders matched the selected filters.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($orders->total() > 0)
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
                            <p class="text-muted mb-0">
                                Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} orders
                            </p>
                            {{ $orders->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>

@endsection
