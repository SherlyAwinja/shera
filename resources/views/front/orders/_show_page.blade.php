@php
    $placedAt = $order->placed_at ?: $order->created_at;
    $orderCode = $order->order_number ?: 'Order #' . $order->id;
    $itemCount = (int) ($order->items_count ?: $order->items->sum('quantity'));
    $statusKey = strtolower(trim((string) $order->order_status));
    $paymentKey = strtolower(trim((string) $order->payment_status));
    $statusKey = $statusKey === 'complete' ? 'completed' : ($statusKey === 'canceled' ? 'cancelled' : $statusKey);
    $formatMoney = fn (float $amount, ?string $currency = null) => (filled($currency) ? strtoupper((string) $currency) . '.' : config('app.currency_symbol', 'KSH.')) . number_format($amount, 2);
    $statusTone = match ($statusKey) {
        'placed', 'confirmed' => 'warning',
        'processing', 'shipped' => 'info',
        'delivered', 'completed' => 'success',
        'cancelled' => 'danger',
        default => 'neutral',
    };
    $paymentTone = match ($paymentKey) {
        'paid' => 'success',
        'partially_paid' => 'warning',
        'failed', 'refunded' => 'danger',
        default => 'neutral',
    };
    $shippingLines = collect([$order->address_line1 ?: optional($order->address)->address_line1, $order->address_line2 ?: optional($order->address)->address_line2, $order->estate ?: optional($order->address)->estate, $order->landmark])->filter()->values();
    $deliveryMeta = collect([$order->sub_county ?: optional($order->address)->sub_county, $order->county ?: optional($order->address)->county, $order->pincode ?: optional($order->address)->pincode, $order->country ?: optional($order->address)->country])->filter()->values();
    $timeline = collect([
        ['key' => 'placed', 'label' => 'Placed', 'icon' => 'fa-receipt', 'copy' => 'The order has been recorded against your account.'],
        ['key' => 'confirmed', 'label' => 'Confirmed', 'icon' => 'fa-check-circle', 'copy' => 'Stock and delivery details are being verified.'],
        ['key' => 'processing', 'label' => 'Processing', 'icon' => 'fa-box-open', 'copy' => 'The fulfilment team is preparing the order.'],
        ['key' => 'shipped', 'label' => 'Shipped', 'icon' => 'fa-truck', 'copy' => 'The parcel is moving through delivery.'],
        ['key' => 'delivered', 'label' => 'Delivered', 'icon' => 'fa-home', 'copy' => 'The shipment reached the delivery destination.'],
        ['key' => 'completed', 'label' => 'Completed', 'icon' => 'fa-check-double', 'copy' => 'The order lifecycle is closed in your history.'],
    ]);
    $timelineIndex = $timeline->search(fn ($step) => $step['key'] === $statusKey);
    $timelineIndex = $timelineIndex === false ? 0 : $timelineIndex;
    $resolveImage = fn ($item) => filled($item->product_image) ? $item->product_image : asset('front/images/products/no-image.jpg');
@endphp

@push('styles')
<style>
    .order-detail-page { padding-bottom: 4rem; }
    .order-detail-hero { background: radial-gradient(circle at 14% 18%, rgba(15, 106, 102, .14), transparent 30%), radial-gradient(circle at 82% 12%, rgba(196, 164, 92, .22), transparent 26%), linear-gradient(135deg, #f4efe5 0%, #fffdfa 52%, #edf7f5 100%); }
    .od-card, .od-stage, .od-item { border: 1px solid rgba(139, 28, 45, .12); border-radius: 28px; box-shadow: 0 20px 44px rgba(26, 26, 26, .08); }
    .od-card { background: var(--front-luxe-surface); padding: 1.35rem; }
    .od-card + .od-card { margin-top: 1.2rem; }
    .od-kicker, .od-side-kicker { display: inline-block; margin-bottom: .45rem; color: var(--front-luxe-accent); font-size: .76rem; font-weight: 700; letter-spacing: .14rem; text-transform: uppercase; }
    .od-title, .od-side-title { font-family: 'Cormorant Garamond', serif; line-height: 1.02; }
    .od-title { font-size: clamp(2rem, 3vw, 2.4rem); margin-bottom: .55rem; }
    .od-side-title { font-size: 1.8rem; margin-bottom: .75rem; }
    .od-copy, .od-meta, .od-address { color: var(--front-luxe-muted); line-height: 1.75; }
    .od-badge, .od-chip, .od-tag, .od-link { display: inline-flex; align-items: center; gap: .55rem; border-radius: 999px; font-weight: 700; text-decoration: none; }
    .od-chip { padding: .78rem 1rem; background: rgba(255, 255, 255, .76); border: 1px solid rgba(18, 50, 59, .08); color: #314047; }
    .od-badge { padding: .58rem .9rem; border: 1px solid transparent; font-size: .83rem; }
    .od-badge[data-tone="success"] { background: rgba(20, 132, 92, .12); border-color: rgba(20, 132, 92, .18); color: #0d7a52; }
    .od-badge[data-tone="warning"] { background: rgba(191, 90, 36, .12); border-color: rgba(191, 90, 36, .16); color: #a24c1f; }
    .od-badge[data-tone="info"] { background: rgba(15, 95, 115, .12); border-color: rgba(15, 95, 115, .18); color: #0f5f73; }
    .od-badge[data-tone="danger"] { background: rgba(139, 28, 45, .1); border-color: rgba(139, 28, 45, .16); color: var(--front-luxe-primary); }
    .od-badge[data-tone="neutral"] { background: rgba(15, 23, 42, .06); border-color: rgba(15, 23, 42, .08); color: #334155; }
    .od-stage { background: #fff; padding: 1rem; display: grid; grid-template-columns: auto 1fr; gap: .9rem; }
    .od-stage + .od-stage { margin-top: .9rem; }
    .od-stage-icon { width: 46px; height: 46px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, .06); color: #475569; }
    .od-stage.is-complete .od-stage-icon { background: rgba(15, 106, 102, .12); color: var(--front-luxe-accent); }
    .od-stage.is-current .od-stage-icon { background: var(--front-luxe-primary); color: #fff; }
    .od-stage-title, .od-item-name, .od-total-value, .od-address-title { color: var(--front-luxe-text); font-weight: 700; }
    .od-alert { margin-top: 1rem; padding: 1rem; border-radius: 20px; background: rgba(139, 28, 45, .08); border: 1px solid rgba(139, 28, 45, .14); color: var(--front-luxe-primary); }
    .od-item { background: #fff; padding: 1rem; display: grid; grid-template-columns: 96px minmax(0, 1fr); gap: 1rem; }
    .od-item + .od-item { margin-top: .9rem; }
    .od-item img { width: 96px; height: 112px; object-fit: cover; border-radius: 18px; border: 1px solid rgba(139, 28, 45, .12); background: #fff; }
    .od-item-top, .od-total-row { display: flex; justify-content: space-between; gap: 1rem; align-items: flex-start; }
    .od-tag { padding: .5rem .8rem; background: rgba(255, 249, 240, .92); border: 1px solid rgba(139, 28, 45, .12); color: var(--front-luxe-text); }
    .od-total-row { padding-bottom: .85rem; margin-bottom: .85rem; border-bottom: 1px solid rgba(139, 28, 45, .08); }
    .od-total-row:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: 0; }
    .od-link { justify-content: center; min-height: 46px; padding: .8rem 1.1rem; border: 1px solid transparent; }
    .od-link:hover, .od-link:focus { text-decoration: none; }
    .od-link--solid { background: var(--front-luxe-primary); color: #fff; }
    .od-link--ghost { background: #fff; border-color: rgba(18, 50, 59, .1); color: var(--front-luxe-text); }
    @media (max-width: 767.98px) { .od-item { grid-template-columns: 1fr; } .od-item img { width: 100%; height: 220px; } }
    @media (max-width: 575.98px) { .od-card, .od-stage, .od-item { border-radius: 24px; } .od-chip, .od-badge, .od-tag, .od-link { width: 100%; justify-content: center; } .od-item-top, .od-total-row { flex-direction: column; } }
</style>
@endpush

@section('content')
<div class="front-luxe-page order-detail-page">
    <div class="container-fluid bg-secondary mb-5 front-page-hero order-detail-hero">
        <div class="front-page-hero-inner">
            <span class="front-page-eyebrow">Order Details</span>
            <h1 class="front-page-title">Status, delivery, and every line item stay tied to the order record.</h1>
            <p class="front-page-subtitle">{{ $orderCode }} was placed {{ optional($placedAt)->format('F j, Y \a\t g:i a') ?: 'recently' }} for {{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }} using {{ $order->payment_method_label }}.</p>

            <div class="d-flex flex-wrap mt-4" style="gap: .75rem;">
                <span class="od-chip"><i class="fa fa-receipt"></i>{{ $orderCode }}</span>
                <span class="od-chip"><i class="fa fa-shopping-bag"></i>{{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }}</span>
                @if ($order->shipping_eta)
                    <span class="od-chip"><i class="fa fa-truck"></i>{{ $order->shipping_eta }}</span>
                @endif
                @if ($order->address_label)
                    <span class="od-chip"><i class="fa fa-map-marker-alt"></i>{{ $order->address_label }}</span>
                @endif
            </div>

            <div class="d-flex flex-wrap mt-4" style="gap: .75rem;">
                <span class="od-badge" data-tone="{{ $statusTone }}"><i class="fa fa-box"></i>{{ $order->status }}</span>
                <span class="od-badge" data-tone="{{ $paymentTone }}"><i class="fa fa-credit-card"></i>{{ $order->payment_status_label }}</span>
            </div>

            <div class="front-page-breadcrumb d-inline-flex flex-wrap mt-4">
                <p class="m-0"><a href="{{ route('home', [], false) }}">Home</a></p>
                <p class="m-0 px-2">/</p>
                <p class="m-0"><a href="{{ route('user.orders.index', [], false) }}">My orders</a></p>
                <p class="m-0 px-2">/</p>
                <p class="m-0">Order details</p>
            </div>
        </div>
    </div>

    <div class="container-fluid front-page-shell">
        <div class="row px-xl-5">
            <div class="col-lg-8 mb-5">
                <div class="od-card">
                    <span class="od-kicker">Fulfilment</span>
                    <h2 class="od-title">Track the order without leaving the detail view.</h2>
                    <p class="od-copy mb-0">The timeline below keeps the current fulfilment stage readable beside the purchase record and totals.</p>

                    @if ($statusKey === 'cancelled')
                        <div class="od-alert">This order is marked as cancelled. Use the order number if you need support to review the cancellation.</div>
                    @else
                        <div class="mt-4">
                            @foreach ($timeline as $index => $step)
                                @php($stageClass = $index < $timelineIndex ? 'is-complete' : ($index === $timelineIndex ? 'is-current' : ''))
                                <div class="od-stage {{ $stageClass }}">
                                    <div class="od-stage-icon"><i class="fa {{ $step['icon'] }}"></i></div>
                                    <div>
                                        <div class="od-stage-title">{{ $step['label'] }}</div>
                                        <div class="od-meta">{{ $step['copy'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="od-card">
                    <span class="od-kicker">Items In This Order</span>
                    <h2 class="od-title">Every product line stays attached to the order history.</h2>
                    <p class="od-copy mb-0">Product names, selected options, quantities, and line totals remain visible long after checkout.</p>

                    <div class="mt-4">
                        @foreach ($order->items as $item)
                            <div class="od-item">
                                <img src="{{ $resolveImage($item) }}" alt="{{ $item->product_name ?: 'Product image' }}">
                                <div>
                                    <div class="od-item-top">
                                        <div>
                                            <div class="od-item-name">{{ $item->product_name ?: optional($item->product)->product_name ?: 'Product' }}</div>
                                            @if (filled($item->product_code))
                                                <div class="od-meta mt-1">Code: {{ $item->product_code }}</div>
                                            @endif
                                        </div>
                                        <div class="od-total-value">{{ $formatMoney((float) $item->subtotal, $order->currency) }}</div>
                                    </div>

                                    <div class="d-flex flex-wrap mt-3" style="gap: .7rem;">
                                        @if (filled($item->size) && strtoupper((string) $item->size) !== 'NA')
                                            <span class="od-tag">Size: {{ $item->size }}</span>
                                        @endif
                                        @if (filled($item->color))
                                            <span class="od-tag">Color: {{ $item->color }}</span>
                                        @endif
                                        <span class="od-tag">Qty: {{ $item->quantity }}</span>
                                        <span class="od-tag">Unit: {{ $formatMoney((float) $item->price, $order->currency) }}</span>
                                    </div>

                                    @if (filled($item->product_url))
                                        <div class="mt-3"><a href="{{ url($item->product_url) }}">View product</a></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="od-card">
                    <span class="od-side-kicker">Totals</span>
                    <div class="od-side-title">Settlement and grand total</div>
                    <div class="od-total-row"><span>Subtotal</span><strong class="od-total-value">{{ $formatMoney((float) $order->subtotal, $order->currency) }}</strong></div>
                    <div class="od-total-row"><span>Discount</span><strong class="od-total-value">{{ (float) $order->discount > 0 ? '-' . $formatMoney((float) $order->discount, $order->currency) : $formatMoney(0, $order->currency) }}</strong></div>
                    @if ((float) $order->wallet_applied_amount > 0)
                        <div class="od-total-row"><span>Wallet Applied</span><strong class="od-total-value">-{{ $formatMoney((float) $order->wallet_applied_amount, $order->currency) }}</strong></div>
                    @endif
                    <div class="od-total-row"><span>Shipping</span><strong class="od-total-value">{{ (float) $order->shipping > 0 ? $formatMoney((float) $order->shipping, $order->currency) : 'Free' }}</strong></div>
                    <div class="od-total-row"><span>Grand Total</span><strong class="od-total-value">{{ $formatMoney((float) $order->total, $order->currency) }}</strong></div>
                </div>

                <div class="od-card">
                    <span class="od-side-kicker">Delivery</span>
                    <div class="od-side-title">Where the order is going</div>
                    <div class="od-address"><strong class="od-address-title">{{ $order->recipient_name }} | {{ $order->recipient_phone }}</strong></div>
                    <div class="od-address mt-2">{{ $shippingLines->isNotEmpty() ? $shippingLines->implode(', ') : 'Delivery address details are still being confirmed.' }}</div>
                    @if ($deliveryMeta->isNotEmpty())
                        <div class="od-address mt-3">{{ $deliveryMeta->implode(' | ') }}</div>
                    @endif
                    <div class="od-address mt-3"><strong class="od-address-title">Delivery Window:</strong> {{ $order->shipping_eta ?: 'Estimated timing will be confirmed during fulfilment.' }}</div>
                </div>

                <div class="od-card">
                    <span class="od-side-kicker">Next Actions</span>
                    <div class="od-side-title">Keep related actions obvious.</div>
                    <p class="od-copy mb-4">Return to the order list, review account settings, or continue browsing products from the storefront.</p>
                    <div class="d-grid" style="gap: .75rem;">
                        <a href="{{ route('user.orders.index', [], false) }}" class="od-link od-link--ghost"><i class="fa fa-arrow-left"></i>Back to orders</a>
                        <a href="{{ route('user.account', [], false) }}" class="od-link od-link--ghost"><i class="fa fa-user"></i>My account</a>
                        <a href="{{ route('home', [], false) }}" class="od-link od-link--solid"><i class="fa fa-store"></i>Continue shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
