@extends('front.layout.layout')

@section('title', 'Order Details')

@include('front.orders._show_page')

{{--
@section('content')
@php
    $shippingLines = collect([
        $order->address_line1 ?: optional($order->address)->address_line1,
        $order->address_line2 ?: optional($order->address)->address_line2,
        $order->estate ?: optional($order->address)->estate,
        $order->sub_county ?: optional($order->address)->sub_county,
        $order->county ?: optional($order->address)->county,
        $order->pincode ?: optional($order->address)->pincode,
        $order->country ?: optional($order->address)->country,
    ])->filter()->values();
@endphp

<!-- Page Header Start -->
<div class="container-fluid bg-secondary mb-5">
    <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 150px">
        <h1 class="font-weight-semi-bold text-uppercase mb-3">Order Details</h1>
        <div class="d-inline-flex">
            <p class="m-0"><a href="{{ url('/') }}">Home</a></p>
            <p class="m-0 px-2">-</p>
            <p class="m-0">Order Details</p>
        </div>
    </div>
</div>
<!-- Page Header End -->

<!-- Order Detail Start -->
<div class="container py-5">

    <!-- Order Info -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-2">
                        Order <span class="text-dark">#{{ $order->id }}</span>
                    </h5>
                    <p class="mb-1">
                        <strong>Date:</strong> {{ $order->created_at->format('d M, Y h:i A') }}
                    </p>
                    <p class="mb-0">
                        <strong>Status:</strong>
                        <span class="badge
                            @if($order->status == 'Pending') bg-warning text-dark
                            @elseif($order->status == 'Completed') bg-success
                            @elseif($order->status == 'Cancelled') bg-danger
                            @else bg-secondary
                            @endif">
                            {{ ucfirst($order->status) }}
                        </span>
                    </p>
                </div>

                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <p class="mb-1">
                        <strong>Total:</strong>
                        {{ config('app.currency_symbol', 'KSH.') }}
                        {{ number_format($order->total ?? 0, 2) }}
                    </p>
                    <p class="mb-0">
                        <strong>Payment Method:</strong>
                        {{ $order->payment_method ?? 'N/A' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Items -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h5 class="mb-3">Items</h5>

            <div class="table-responsive">
                <table class="table table-borderless align-middle">
                    <tbody>
                        @foreach($order->items as $item)
                            <tr class="border-top">
                                <td style="width:80px;">
                                    @if(optional($item->product)->image)
                                        <img src="{{ asset('storage/' . $item->product->image) }}"
                                             alt="{{ $item->product->product_name }}"
                                             class="img-fluid"
                                             style="max-width:70px;">
                                    @else
                                        <div class="bg-light d-flex align-items-center justify-content-center"
                                             style="width:70px;height:70px;">
                                            No Image
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        {{ optional($item->product)->product_name ?? $item->product_name }}
                                    </div>
                                    <div class="text-muted small">
                                        Size: {{ $item->size }}
                                    </div>
                                    <div class="text-muted small">
                                        Color: {{ $item->color }}
                                    </div>
                                    <div class="text-muted small">
                                        Qty: {{ $item->quantity }} ×
                                        {{ config('app.currency_symbol', 'KSH.') }}
                                        {{ number_format($item->price, 2) }}
                                    </div>
                                </td>

                                <td class="text-end fw-semibold" style="width:140px;">
                                    {{ config('app.currency_symbol', 'KSH.') }}
                                    {{ number_format($item->subtotal, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <!-- Shipping & Billing -->
    <div class="row gy-4">

        <!-- Shipping -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="mb-3">Shipping Address</h6>

                    @if($order->recipient_name || $shippingLines->isNotEmpty() || $order->address)
                        <p class="mb-1">{{ $order->recipient_name ?: optional($order->address)->recipient_name ?: '' }}</p>
                        @if($order->recipient_phone || optional($order->address)->recipient_phone)
                            <p class="mb-1">{{ $order->recipient_phone ?: optional($order->address)->recipient_phone }}</p>
                        @endif
                        @foreach($shippingLines as $line)
                            <p class="mb-1">{{ $line }}</p>
                        @endforeach
                    @else
                        <p class="mb-0">N/A</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Billing Summary -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h6 class="mb-3">Billing Summary</h6>

                    <div class="d-flex justify-content-between mb-2">
                        <div>Subtotal</div>
                        <div>
                            {{ config('app.currency_symbol', 'KSH.') }}
                            {{ number_format($order->subtotal ?? 0, 2) }}
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <div>Shipping</div>
                        <div>
                            {{ config('app.currency_symbol', 'KSH.') }}
                            {{ number_format($order->shipping ?? 0, 2) }}
                        </div>
                    </div>

                    @if(!empty($order->discount))
                        <div class="d-flex justify-content-between mb-2">
                            <div>Discount</div>
                            <div>
                                -{{ config('app.currency_symbol', 'KSH.') }}
                                {{ number_format($order->discount, 2) }}
                            </div>
                        </div>
                    @endif

                    <hr>

                    <div class="d-flex justify-content-between fw-bold">
                        <div>Grand Total</div>
                        <div>
                            {{ config('app.currency_symbol', 'KSH.') }}
                            {{ number_format($order->total ?? 0, 2) }}
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Back Button -->
    <div class="mt-4">
        <a href="{{ route('user.orders.index') }}" class="btn btn-outline-secondary">
            Back to Orders
        </a>
    </div>

</div>
<!-- Order Detail End -->

@endsection
--}}
