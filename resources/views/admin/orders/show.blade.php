@extends('admin.layout.layout')

@section('content')
@php
    $currency = $order->currency ?? 'KES';
    $orderStatusBadgeClass = match ($order->order_status) {
        'placed' => 'text-bg-warning text-dark',
        'confirmed' => 'text-bg-info',
        'processing' => 'text-bg-primary',
        'shipped' => 'text-bg-info',
        'delivered', 'completed' => 'text-bg-success',
        'cancelled' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };
    $paymentStatusBadgeClass = match ($order->payment_status) {
        'paid' => 'text-bg-success',
        'partially_paid' => 'text-bg-warning text-dark',
        'failed' => 'text-bg-danger',
        'refunded' => 'text-bg-dark',
        default => 'text-bg-secondary',
    };
    $deliveryLines = collect([
        $order->address_line1 ?: optional($order->address)->address_line1,
        $order->address_line2 ?: optional($order->address)->address_line2,
        $order->estate ?: optional($order->address)->estate,
        $order->sub_county ?: optional($order->address)->sub_county,
        $order->county ?: optional($order->address)->county,
        $order->pincode ?: optional($order->address)->pincode,
        $order->country ?: optional($order->address)->country,
        $order->landmark ?: optional($order->address)->landmark,
    ])->filter()->values();
    $canEditOrder = ($orderModule['edit_access'] ?? 0) == 1 || ($orderModule['full_access'] ?? 0) == 1;
@endphp

<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">{{ $order->order_number ?: 'Order #' . $order->id }}</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('orders.index') }}">Orders</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Details</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
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

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h3 class="card-title mb-1">Order Overview</h3>
                                <p class="text-muted mb-0 small">Track customer, payment, and fulfillment state for this order.</p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge {{ $orderStatusBadgeClass }}">{{ $order->order_status_label }}</span>
                                <span class="badge {{ $paymentStatusBadgeClass }}">{{ $order->payment_status_label }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="text-muted small text-uppercase">Customer</div>
                                    <div class="fw-semibold">{{ optional($order->user)->name ?: ($order->recipient_name ?: 'Guest User') }}</div>
                                    <div class="small text-muted">{{ $order->email ?: (optional($order->user)->email ?: 'No email address') }}</div>
                                    @if($order->recipient_phone)
                                        <div class="small text-muted">{{ $order->recipient_phone }}</div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small text-uppercase">Placed On</div>
                                    <div class="fw-semibold">{{ optional($order->placed_at ?? $order->created_at)->format('F j, Y, g:i a') }}</div>
                                    <div class="small text-muted">Reference: {{ $order->order_uuid ?: 'Unavailable' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small text-uppercase">Payment Method</div>
                                    <div class="fw-semibold">{{ $order->payment_method_label }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small text-uppercase">Items</div>
                                    <div class="fw-semibold">{{ number_format((int) $order->items_count) }}</div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-muted small text-uppercase">Grand Total</div>
                                    <div class="fw-semibold">{{ $currency }} {{ number_format($order->total, 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Order Items</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Product</th>
                                            <th>Variant</th>
                                            <th>Qty</th>
                                            <th>Unit Price</th>
                                            <th>Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($order->items as $item)
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold">{{ optional($item->product)->product_name ?: $item->product_name }}</div>
                                                    @if($item->product_code)
                                                        <div class="small text-muted">Code: {{ $item->product_code }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="small">
                                                        <strong>Size:</strong> {{ $item->size ?: 'N/A' }}
                                                    </div>
                                                    <div class="small">
                                                        <strong>Color:</strong> {{ $item->color ?: 'N/A' }}
                                                    </div>
                                                </td>
                                                <td>{{ number_format((int) $item->quantity) }}</td>
                                                <td>{{ $currency }} {{ number_format($item->price, 2) }}</td>
                                                <td>{{ $currency }} {{ number_format($item->subtotal, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">No items were found for this order.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5><strong>Update Order Status</strong></h5>

                        @if(session('success_message'))
                            <div class="alert alert-success">
                                {{ session('success_message') }}
                            </div>
                        @endif

                        @if(session('error_message'))
                            <div class="alert alert-danger">
                                {{ session('error_message') }}
                            </div>
                        @endif

                        <form action="{{ route('orders.update-status', $order->id) }}" method="POST">
                            @csrf

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="order_status_id">Order Status</label>
                                    <select name="order_status_id" id="order_status_id" class="form-control">
                                        <option value="">-- Select Status --</option>
                                        @foreach($statuses as $s)
                                            <option value="{{ $s->id }}"
                                                {{ strtolower($order->status) === strtolower($s->name) ? 'selected' : '' }}>
                                                {{ $s->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 shipped-field" style="display:none;">
                                    <label for="tracking_number">Order Tracking</label>
                                    <input type="text" name="tracking_number" id="tracking_number"
                                        class="form-control"
                                        value="{{ $order->tracking_number }}">
                                </div>

                                <div class="col-md-12 shipped-field" style="display:none; margin-top:8px;">
                                    <label for="tracking_link">Tracking Link (optional)</label>

                                    <input
                                        type="url"
                                        name="tracking_link"
                                        id="tracking_link"
                                        class="form-control"
                                        placeholder="https://track.example.com/parcel/ABC123"
                                        value="{{ $order->tracking_link ?? '' }}"
                                    >

                                    <small class="form-text text-muted">
                                        If you paste the carrier's tracking URL here, the email will include a direct clickable link.
                                    </small>
                                </div>

                                <div class="col-md-4 shipped-field" style="display:none;">
                                    <label for="shipping_partner">Shipping Partner</label>
                                    <input type="text" name="shipping_partner" id="shipping_partner"
                                        class="form-control"
                                        value="{{ $order->shipping_partner }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="remarks">Remarks</label>
                                <textarea name="remarks" id="remarks" rows="3" class="form-control"></textarea>
                            </div>

                            <button class="btn btn-primary" type="submit">Update Status</button>
                        </form>

                        <hr>

                        <h6><strong>Order Logs</strong></h6>

                        <table class="table table-sm mt-3">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Tracking</th>
                                    <th>Partner</th>
                                    <th>Remarks</th>
                                    <th>Updated By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                                        <td>{{ optional($log->status)->name ?? '-' }}</td>
                                        <td>
                                            {{ $log->tracking_number ?? '-' }}
                                            @if(!empty($log->tracking_link))
                                                <td>
                                                    <a href="{{ $log->tracking_link }}" target="_blank">
                                                        {{ $log->shipping_partner ?? 'Track Link' }}
                                                    </a>
                                                </td>
                                            @endif
                                        </td>
                                        <td>{{ $log->shipping_partner ?? '-' }}</td>
                                        <td>{{ $log->remarks ?? '--' }}</td>
                                        <td>
                                            {{ optional($log->updatedByAdmin)->name ?? 'Admin #' . $log->updated_by }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">No logs yet</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Manage Order</h3>
                        </div>
                        <div class="card-body">
                            @if($canEditOrder)
                                <form action="{{ route('orders.update', $order->id) }}" method="POST" class="row g-3">
                                    @csrf
                                    @method('PATCH')
                                    <div class="col-12">
                                        <label for="orderStatusField" class="form-label">Order Status</label>
                                        <select name="order_status" id="orderStatusField" class="form-select">
                                            @foreach($orderStatusOptions as $value => $label)
                                                <option value="{{ $value }}" {{ $order->order_status === $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="paymentStatusField" class="form-label">Payment Status</label>
                                        <select name="payment_status" id="paymentStatusField" class="form-select">
                                            @foreach($paymentStatusOptions as $value => $label)
                                                <option value="{{ $value }}" {{ $order->payment_status === $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100">Save Order Update</button>
                                    </div>
                                </form>
                            @else
                                <p class="text-muted mb-0">You have view-only access for this order.</p>
                            @endif
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Delivery Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="fw-semibold">{{ $order->recipient_name ?: optional($order->address)->recipient_name ?: 'No recipient name' }}</div>
                            @if($order->recipient_phone || optional($order->address)->recipient_phone)
                                <div class="small text-muted mb-2">{{ $order->recipient_phone ?: optional($order->address)->recipient_phone }}</div>
                            @endif
                            @forelse($deliveryLines as $line)
                                <div class="small">{{ $line }}</div>
                            @empty
                                <div class="text-muted small">No delivery address was stored for this order.</div>
                            @endforelse
                            @if($order->address_label)
                                <div class="mt-3">
                                    <span class="badge text-bg-light">{{ $order->address_label }}</span>
                                </div>
                            @endif
                            @if($order->shipping_zone || $order->shipping_eta)
                                <hr>
                                @if($order->shipping_zone)
                                    <div class="small"><strong>Shipping Zone:</strong> {{ $order->shipping_zone }}</div>
                                @endif
                                @if($order->shipping_eta)
                                    <div class="small"><strong>ETA:</strong> {{ $order->shipping_eta }}</div>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">Payment Summary</h3>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <strong>{{ $currency }} {{ number_format($order->subtotal, 2) }}</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping</span>
                                <strong>{{ $currency }} {{ number_format($order->shipping, 2) }}</strong>
                            </div>
                            @if((float) $order->discount > 0)
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Discount</span>
                                    <strong>-{{ $currency }} {{ number_format($order->discount, 2) }}</strong>
                                </div>
                            @endif
                            @if((float) $order->wallet_applied_amount > 0)
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Wallet Applied</span>
                                    <strong>-{{ $currency }} {{ number_format((float) $order->wallet_applied_amount, 2) }}</strong>
                                </div>
                            @endif
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">Grand Total</span>
                                <strong>{{ $currency }} {{ number_format($order->total, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary">Back to Orders</a>
            </div>
        </div>
    </div>
</main>

@endsection
