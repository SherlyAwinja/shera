@extends('front.layout.layout')

@section('title', 'My Orders')

@php
    $ordersCollection = $orders->getCollection();
    $displayName = auth()->user()?->name ?: auth()->user()?->email ?: 'there';
    $visibleSpend = (float) $ordersCollection->sum(fn ($order) => (float) $order->total);
    $openVisibleOrders = $ordersCollection->filter(fn ($order) => ! in_array(strtolower(trim((string) $order->order_status)), ['delivered', 'completed', 'cancelled', 'canceled'], true))->count();
    $latestOrderDate = $ordersCollection->first()?->placed_at ?: $ordersCollection->first()?->created_at;
    $formatMoney = fn (float $amount, ?string $currency = null) => (filled($currency) ? strtoupper((string) $currency) . '.' : config('app.currency_symbol', 'KSH.')) . number_format($amount, 2);
    $statusTone = fn (?string $status) => match (strtolower(trim((string) $status))) {
        'placed', 'confirmed' => 'warning',
        'processing', 'shipped' => 'info',
        'delivered', 'completed' => 'success',
        'cancelled', 'canceled' => 'danger',
        default => 'neutral',
    };
    $paymentTone = fn (?string $status) => match (strtolower(trim((string) $status))) {
        'paid' => 'success',
        'partially_paid' => 'warning',
        'failed', 'refunded' => 'danger',
        default => 'neutral',
    };
@endphp

@push('styles')
<style>
    .orders-page { padding-bottom: 4rem; }
    .orders-hero { background: radial-gradient(circle at 14% 18%, rgba(15, 106, 102, .14), transparent 30%), radial-gradient(circle at 82% 12%, rgba(196, 164, 92, .22), transparent 26%), linear-gradient(135deg, #f4efe5 0%, #fffdfa 52%, #edf7f5 100%); }
    .orders-shell, .orders-sidebar, .orders-card, .orders-empty { border: 1px solid rgba(139, 28, 45, .12); border-radius: 28px; box-shadow: 0 20px 44px rgba(26, 26, 26, .08); }
    .orders-shell { background: linear-gradient(180deg, rgba(255, 255, 255, .96) 0%, rgba(255, 249, 240, .96) 100%); padding: 2rem; }
    .orders-sidebar { height: 100%; padding: 2rem; color: #fff; background: linear-gradient(160deg, #12323b 0%, #0f5f73 100%); }
    .orders-kicker { display: inline-block; margin-bottom: .8rem; color: #c4a45c; font-size: .76rem; font-weight: 700; letter-spacing: .18rem; text-transform: uppercase; }
    .orders-title, .orders-section-title, .orders-empty-title { font-family: 'Cormorant Garamond', serif; line-height: 1.02; }
    .orders-title { color: #fff; font-size: clamp(2rem, 3vw, 2.6rem); margin-bottom: .9rem; }
    .orders-copy, .orders-note, .orders-preview-text, .orders-empty-copy { line-height: 1.75; }
    .orders-copy { color: rgba(255, 255, 255, .82); margin-bottom: 1.3rem; }
    .orders-chip, .orders-badge, .orders-nav-link, .orders-link { display: inline-flex; align-items: center; gap: .55rem; border-radius: 999px; font-weight: 700; text-decoration: none; }
    .orders-chip { padding: .78rem 1rem; background: rgba(255, 255, 255, .76); border: 1px solid rgba(18, 50, 59, .08); color: #314047; }
    .orders-stat, .orders-metric { border-radius: 20px; padding: .95rem 1rem; }
    .orders-stat { background: rgba(255, 255, 255, .1); border: 1px solid rgba(255, 255, 255, .12); }
    .orders-stat-label, .orders-metric-label, .orders-section-kicker { display: inline-block; margin-bottom: .35rem; font-size: .76rem; font-weight: 700; letter-spacing: .14rem; text-transform: uppercase; }
    .orders-stat-label { color: rgba(255, 255, 255, .7); }
    .orders-stat-value { color: #fff; font-size: 1.2rem; font-weight: 700; }
    .orders-nav-link { justify-content: space-between; width: 100%; padding: .9rem 1rem; color: #fff; background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .14); }
    .orders-nav-link:hover, .orders-nav-link:focus { color: #fff; text-decoration: none; background: rgba(255, 255, 255, .16); }
    .orders-section-kicker, .orders-metric-label { color: var(--front-luxe-accent); }
    .orders-section-title { font-size: clamp(2rem, 3vw, 2.4rem); margin-bottom: .45rem; }
    .orders-note { color: var(--front-luxe-muted); margin-bottom: 0; }
    .orders-card { background: #fff; padding: 1.25rem; }
    .orders-card + .orders-card { margin-top: 1rem; }
    .orders-card-head, .orders-card-grid, .orders-card-actions { display: flex; flex-wrap: wrap; gap: .85rem; }
    .orders-card-head { justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
    .orders-order-number { color: var(--front-luxe-text); font-size: 1.15rem; font-weight: 700; }
    .orders-order-meta { margin-top: .35rem; color: var(--front-luxe-muted); }
    .orders-badge { padding: .58rem .9rem; border: 1px solid transparent; font-size: .83rem; }
    .orders-badge[data-tone="success"] { background: rgba(20, 132, 92, .12); border-color: rgba(20, 132, 92, .18); color: #0d7a52; }
    .orders-badge[data-tone="warning"] { background: rgba(191, 90, 36, .12); border-color: rgba(191, 90, 36, .16); color: #a24c1f; }
    .orders-badge[data-tone="info"] { background: rgba(15, 95, 115, .12); border-color: rgba(15, 95, 115, .18); color: #0f5f73; }
    .orders-badge[data-tone="danger"] { background: rgba(139, 28, 45, .1); border-color: rgba(139, 28, 45, .16); color: var(--front-luxe-primary); }
    .orders-badge[data-tone="neutral"] { background: rgba(15, 23, 42, .06); border-color: rgba(15, 23, 42, .08); color: #334155; }
    .orders-card-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 1rem; }
    .orders-metric { background: rgba(255, 249, 240, .72); border: 1px solid rgba(139, 28, 45, .1); }
    .orders-metric strong { color: var(--front-luxe-text); display: block; }
    .orders-preview { padding: .95rem 1rem; border-radius: 18px; background: rgba(15, 106, 102, .06); border: 1px solid rgba(15, 106, 102, .12); }
    .orders-preview-text { color: var(--front-luxe-muted); margin: 0; }
    .orders-link { justify-content: center; min-height: 46px; padding: .8rem 1.1rem; border: 1px solid transparent; }
    .orders-link:hover, .orders-link:focus { text-decoration: none; }
    .orders-link--solid { background: var(--front-luxe-primary); color: #fff; }
    .orders-link--ghost { background: #fff; border-color: rgba(18, 50, 59, .1); color: var(--front-luxe-text); }
    .orders-empty { background: #fff; text-align: center; padding: 3rem 1.25rem; }
    .orders-empty-icon { width: 82px; height: 82px; margin: 0 auto 1rem; border-radius: 24px; display: flex; align-items: center; justify-content: center; background: rgba(15, 106, 102, .1); color: var(--front-luxe-accent); font-size: 1.9rem; }
    .orders-empty-title { font-size: 2rem; margin-bottom: .55rem; }
    .orders-empty-copy { color: var(--front-luxe-muted); max-width: 34rem; margin: 0 auto 1.1rem; }
    .orders-pagination .pagination { gap: .45rem; justify-content: center; }
    .orders-pagination .page-link { min-width: 42px; min-height: 42px; border-radius: 999px !important; border: 1px solid rgba(139, 28, 45, .12); color: var(--front-luxe-text); box-shadow: none; }
    .orders-pagination .page-item.active .page-link { background: var(--front-luxe-primary); border-color: var(--front-luxe-primary); color: #fff; }
    @media (max-width: 991.98px) { .orders-shell, .orders-sidebar { padding: 1.5rem; } }
    @media (max-width: 767.98px) { .orders-card-grid { grid-template-columns: 1fr; } }
    @media (max-width: 575.98px) { .orders-shell, .orders-sidebar, .orders-card, .orders-empty { border-radius: 24px; } .orders-chip, .orders-badge, .orders-link { width: 100%; justify-content: center; } }
</style>
@endpush

@section('content')
<div class="front-luxe-page orders-page">
    <div class="container-fluid bg-secondary mb-5 front-page-hero orders-hero">
        <div class="front-page-hero-inner">
            <span class="front-page-eyebrow">My Orders</span>
            <h1 class="front-page-title">Order history with live fulfilment and payment context.</h1>
            <p class="front-page-subtitle">Hi, {{ $displayName }}. Every submitted order stays visible here with the current status, payment label, and total.</p>

            <div class="d-flex flex-wrap mt-4" style="gap: .75rem;">
                <span class="orders-chip"><i class="fa fa-box"></i>{{ $orders->total() }} order{{ $orders->total() === 1 ? '' : 's' }}</span>
                <span class="orders-chip"><i class="fa fa-spinner"></i>{{ $openVisibleOrders }} active</span>
                <span class="orders-chip"><i class="fa fa-wallet"></i>{{ $formatMoney($visibleSpend) }} visible value</span>
                @if ($latestOrderDate)
                    <span class="orders-chip"><i class="fa fa-calendar-alt"></i>Latest {{ $latestOrderDate->format('M j, Y') }}</span>
                @endif
            </div>

            <div class="front-page-breadcrumb d-inline-flex flex-wrap mt-4">
                <p class="m-0"><a href="{{ route('home', [], false) }}">Home</a></p>
                <p class="m-0 px-2">/</p>
                <p class="m-0">My orders</p>
            </div>
        </div>
    </div>

    <div class="container-fluid front-page-shell">
        <div class="row px-xl-5">
            <div class="col-lg-4 col-xl-3 mb-4">
                <aside class="orders-sidebar">
                    <span class="orders-kicker">Overview</span>
                    <h2 class="orders-title">Keep order history and account actions in one flow.</h2>
                    <p class="orders-copy">Use this area to jump back into account settings, wallet activity, or product browsing after you review an order.</p>

                    <div class="orders-stat mb-3">
                        <span class="orders-stat-label">Orders Recorded</span>
                        <div class="orders-stat-value">{{ $orders->total() }}</div>
                    </div>
                    <div class="orders-stat mb-3">
                        <span class="orders-stat-label">Active Orders</span>
                        <div class="orders-stat-value">{{ $openVisibleOrders }}</div>
                    </div>
                    <div class="orders-stat mb-4">
                        <span class="orders-stat-label">Visible Spend</span>
                        <div class="orders-stat-value">{{ $formatMoney($visibleSpend) }}</div>
                    </div>

                    <div class="d-grid" style="gap: .75rem;">
                        <a href="{{ route('user.account', [], false) }}" class="orders-nav-link"><span>My Account</span><i class="fas fa-user"></i></a>
                        <a href="{{ route('user.account.wallet', [], false) }}" class="orders-nav-link"><span>Wallet</span><i class="fas fa-wallet"></i></a>
                        <a href="{{ route('home', [], false) }}" class="orders-nav-link"><span>Continue Shopping</span><i class="fas fa-store"></i></a>
                    </div>
                </aside>
            </div>

            <div class="col-lg-8 col-xl-9">
                <section class="orders-shell">
                    <span class="orders-section-kicker">Order History</span>
                    <h2 class="orders-section-title">Every order opens with the details that matter first.</h2>
                    <p class="orders-note mb-4">
                        @if ($orders->total() > 0)
                            Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} order{{ $orders->total() === 1 ? '' : 's' }}.
                        @else
                            No orders have been placed on this account yet.
                        @endif
                    </p>

                    @forelse ($orders as $order)
                        @php
                            $itemCount = (int) ($order->items_count ?: $order->items->sum('quantity'));
                            $placedAt = $order->placed_at ?: $order->created_at;
                            $previewItems = $order->items->take(3)->map(fn ($item) => $item->product_name ?: optional($item->product)->product_name ?: 'Product')->implode(' - ');
                            $shippingLabel = $order->shipping_eta ?: ($order->shipping_zone ? 'Zone ' . $order->shipping_zone : 'To be confirmed');
                        @endphp

                        <article class="orders-card">
                            <div class="orders-card-head">
                                <div>
                                    <div class="orders-order-number">{{ $order->order_number ?: 'Order #' . $order->id }}</div>
                                    <div class="orders-order-meta">Placed {{ optional($placedAt)->format('F j, Y \a\t g:i a') ?: 'Recently' }} | {{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }}</div>
                                </div>
                                <div class="orders-card-actions">
                                    <span class="orders-badge" data-tone="{{ $statusTone($order->order_status) }}"><i class="fa fa-box"></i>{{ $order->status }}</span>
                                    <span class="orders-badge" data-tone="{{ $paymentTone($order->payment_status) }}"><i class="fa fa-credit-card"></i>{{ $order->payment_status_label }}</span>
                                </div>
                            </div>

                            <div class="orders-card-grid">
                                <div class="orders-metric">
                                    <span class="orders-metric-label">Grand Total</span>
                                    <strong>{{ $formatMoney((float) $order->total, $order->currency) }}</strong>
                                </div>
                                <div class="orders-metric">
                                    <span class="orders-metric-label">Payment Method</span>
                                    <strong>{{ $order->payment_method_label }}</strong>
                                </div>
                                <div class="orders-metric">
                                    <span class="orders-metric-label">Delivery Window</span>
                                    <strong>{{ $shippingLabel }}</strong>
                                </div>
                                <div class="orders-metric">
                                    <span class="orders-metric-label">Destination</span>
                                    <strong>{{ $order->county ?: $order->country }}</strong>
                                </div>
                            </div>

                            <div class="orders-preview mb-3">
                                <p class="orders-preview-text">{{ $previewItems ?: 'Line items are available in the detail view.' }}</p>
                            </div>

                            <div class="orders-card-actions">
                                <a href="{{ route('user.orders.show', ['order' => $order->id], false) }}" class="orders-link orders-link--solid"><i class="fa fa-eye"></i>View details</a>
                                <a href="{{ route('home', [], false) }}" class="orders-link orders-link--ghost"><i class="fa fa-arrow-left"></i>Shop again</a>
                            </div>
                        </article>
                    @empty
                        <div class="orders-empty">
                            <div class="orders-empty-icon"><i class="fa fa-shopping-bag"></i></div>
                            <h3 class="orders-empty-title">No purchases yet.</h3>
                            <p class="orders-empty-copy">Once checkout is completed, this page will list each order with its fulfilment and payment state.</p>
                            <div class="d-flex flex-wrap justify-content-center" style="gap: .75rem;">
                                <a href="{{ route('home', [], false) }}" class="orders-link orders-link--solid"><i class="fa fa-store"></i>Start shopping</a>
                                <a href="{{ route('user.account', [], false) }}" class="orders-link orders-link--ghost"><i class="fa fa-user"></i>Review account</a>
                            </div>
                        </div>
                    @endforelse

                    @if ($orders->hasPages())
                        <div class="orders-pagination mt-4">
                            {{ $orders->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
