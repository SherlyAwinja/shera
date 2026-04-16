@extends('front.layout.layout')

@php
    $paymentMethodLabel = data_get(config('checkout.payment.methods', []), $order->payment_method . '.label', \Illuminate\Support\Str::headline(str_replace('_', ' ', (string) $order->payment_method)));
    $currency = (string) ($order->currency ?: 'KSH');
    $money = fn (float $amount) => $currency . '.' . number_format($amount, 2);
    $itemCount = (int) $order->items->sum('quantity');
    $placedAt = $order->placed_at?->format('D, d M Y \a\t H:i') ?: 'Just now';
    $deliveryLine = collect([$order->address_line1, $order->address_line2, $order->estate, $order->landmark])->filter()->implode(', ');
    $deliveryMeta = collect([$order->sub_county, $order->county, $order->pincode, $order->country])->filter()->implode(' | ');
    [$statusLabel, $statusTone, $statusCopy] = match ((string) $order->payment_status) {
        'paid' => ['Paid', 'paid', 'Payment is settled and the order is already cleared for processing.'],
        'partially_paid' => ['Partially Paid', 'partial', 'Wallet credit has reduced the balance and the remaining amount follows the chosen payment method.'],
        default => ['Pending Payment', 'pending', 'The order is placed and waiting for collection through the selected payment route.'],
    };
    $nextSteps = match ((string) $order->payment_method) {
        'cod' => [
            'We verify stock and lock the delivery details.',
            'The parcel moves into packing and dispatch preparation.',
            'Payment is collected when the order arrives.',
        ],
        'wallet' => [
            'Your wallet already covered the order total.',
            'The team can move directly into fulfilment and dispatch.',
            'The next update should be shipping progress.',
        ],
        'bank_transfer' => [
            'Keep the order number close when confirming the transfer.',
            'The store verifies settlement against this order.',
            'Dispatch begins after payment confirmation.',
        ],
        default => [
            'The order is stored with the selected payment preference.',
            'The team reviews stock and delivery readiness.',
            'The next update should be a processing or dispatch notice.',
        ],
    };
@endphp

@push('styles')
<style>
    .oc-page{padding-bottom:4rem}.oc-hero{position:relative;overflow:hidden;border-bottom:0;background:radial-gradient(circle at 14% 16%,rgba(15,106,102,.14),transparent 30%),radial-gradient(circle at 84% 12%,rgba(196,164,92,.22),transparent 26%),linear-gradient(135deg,#f4efe5 0%,#fffdf9 48%,#edf7f5 100%)}.oc-hero:before,.oc-hero:after{content:"";position:absolute;border-radius:999px;pointer-events:none}.oc-hero:before{width:360px;height:360px;right:-120px;top:-150px;background:rgba(18,50,59,.05)}.oc-hero:after{width:260px;height:260px;left:-90px;bottom:-110px;background:rgba(139,28,45,.08)}.oc-hero .front-page-hero-inner{max-width:1240px;min-height:auto;padding:4.15rem 1rem 3.5rem;position:relative;z-index:1}.oc-grid{display:grid;grid-template-columns:minmax(0,1.32fr) minmax(320px,.96fr);gap:2rem;align-items:end}.oc-copy{max-width:740px}.oc-eyebrow{display:inline-flex;align-items:center;gap:.6rem;margin-bottom:1rem;padding:.62rem 1rem;border-radius:999px;background:rgba(255,255,255,.82);border:1px solid rgba(15,106,102,.12);box-shadow:0 12px 28px rgba(18,50,59,.08)}.oc-title{max-width:720px;margin-bottom:.9rem}.oc-subtitle{max-width:650px;margin:0;color:#5f6368}.oc-chip-row,.oc-actions,.oc-tags{display:flex;flex-wrap:wrap;gap:.75rem}.oc-chip-row{margin:1.35rem 0 1.2rem}.oc-chip,.oc-tag{display:inline-flex;align-items:center;gap:.48rem;padding:.72rem .96rem;border-radius:999px;background:rgba(255,255,255,.74);border:1px solid rgba(18,50,59,.08);font-size:.85rem;font-weight:700;color:#314047}.oc-actions{margin-top:1.2rem}.oc-link{display:inline-flex;align-items:center;gap:.6rem;min-height:50px;padding:.82rem 1.18rem;border-radius:999px;font-weight:700}.oc-link:hover{text-decoration:none}.oc-link--solid{background:var(--front-luxe-primary);color:#fff;box-shadow:0 18px 32px rgba(18,50,59,.18)}.oc-link--ghost{background:rgba(255,255,255,.76);color:var(--front-luxe-text);border:1px solid rgba(18,50,59,.1)}.oc-hero .front-page-breadcrumb p,.oc-hero .front-page-breadcrumb a{color:rgba(18,50,59,.78)}.oc-panel{position:relative;padding:1.55rem;border-radius:32px;background:rgba(18,50,59,.95);color:#fff;box-shadow:0 28px 54px rgba(18,50,59,.22);overflow:hidden}.oc-panel:before{content:"";position:absolute;inset:auto -16% -48% auto;width:240px;height:240px;border-radius:50%;background:radial-gradient(circle,rgba(196,164,92,.22),transparent 68%)}.oc-panel-top{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;margin-bottom:1rem}.oc-panel-kicker,.oc-kicker{display:block;font-size:.74rem;letter-spacing:.18rem;text-transform:uppercase;font-weight:700}.oc-panel-kicker{color:rgba(255,255,255,.68)}.oc-kicker{color:var(--front-luxe-accent);margin-bottom:.55rem}.oc-panel h3,.oc-heading{font-family:"Cormorant Garamond",Georgia,serif;line-height:1}.oc-panel h3{font-size:2rem;margin:.35rem 0 0}.oc-heading{font-size:1.95rem;margin-bottom:.45rem}.oc-pill{display:inline-flex;align-items:center;gap:.45rem;padding:.55rem .85rem;border-radius:999px;font-size:.82rem;font-weight:700;border:1px solid transparent}.oc-pill[data-tone=paid]{background:rgba(34,197,94,.14);border-color:rgba(34,197,94,.22);color:#bbf7d0}.oc-pill[data-tone=partial]{background:rgba(245,158,11,.14);border-color:rgba(245,158,11,.22);color:#fde68a}.oc-pill[data-tone=pending]{background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.12);color:#fff3cd}.oc-stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}.oc-stat{padding:1rem;border-radius:22px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.08)}.oc-stat-label{display:block;font-size:.72rem;letter-spacing:.12rem;text-transform:uppercase;color:rgba(255,255,255,.58);margin-bottom:.35rem}.oc-stat-value{display:block;font-size:1.16rem;font-weight:700;line-height:1.15}.oc-panel-note{margin-top:1rem;padding:1rem 1.05rem;border-radius:24px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.08)}.oc-panel-note p,.oc-copy-text,.oc-meta{margin:0;color:var(--front-luxe-muted);line-height:1.7}.oc-panel-note strong{display:block;font-size:1rem}.oc-panel-note p{margin-top:.45rem;color:rgba(255,255,255,.72)}.oc-card{background:var(--front-luxe-surface);border:1px solid rgba(139,28,45,.12);border-radius:28px;box-shadow:0 20px 44px rgba(26,26,26,.08);padding:1.35rem}.oc-card+.oc-card{margin-top:1.25rem}.oc-alert{margin-bottom:1.15rem;border-radius:20px;border:1px solid rgba(15,106,102,.12);background:rgba(239,247,246,.72);padding:1rem 1.1rem}.oc-step-list,.oc-item-list{display:grid;gap:1rem;margin-top:1rem}.oc-step{display:grid;grid-template-columns:auto 1fr;gap:.9rem;padding:1rem;border:1px solid rgba(139,28,45,.12);border-radius:22px;background:#fff}.oc-step-no{width:42px;height:42px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;background:rgba(15,106,102,.1);color:var(--front-luxe-accent);font-weight:700}.oc-step-title,.oc-side-title,.oc-item-name{font-size:1rem;font-weight:700;color:var(--front-luxe-text)}.oc-item{display:grid;grid-template-columns:96px minmax(0,1fr);gap:1rem;padding:1rem;border:1px solid rgba(139,28,45,.12);border-radius:24px;background:#fff}.oc-item img{width:96px;height:112px;object-fit:cover;border-radius:18px;border:1px solid rgba(139,28,45,.12);background:#fff}.oc-item-top,.oc-total{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start}.oc-item-price{font-weight:700;color:var(--front-luxe-text);white-space:nowrap}.oc-tags{margin:.7rem 0}.oc-total{align-items:center;padding-bottom:.9rem;margin-bottom:.9rem;border-bottom:1px solid rgba(139,28,45,.08)}.oc-total:last-child{margin-bottom:0;padding-bottom:0;border-bottom:0}.oc-total--grand{font-size:1.02rem}.oc-side-title{margin-bottom:.8rem}.oc-help{margin-top:1rem}@media (max-width:991.98px){.oc-grid{grid-template-columns:1fr}}@media (max-width:767.98px){.oc-stats{grid-template-columns:1fr}}@media (max-width:575.98px){.oc-actions{flex-direction:column}.oc-link{justify-content:center}.oc-item{grid-template-columns:1fr}.oc-item img{width:100%;height:220px}.oc-item-top{flex-direction:column}}
</style>
@endpush

@section('content')
<div class="front-luxe-page oc-page">
    <div class="container-fluid bg-secondary mb-5 front-page-hero oc-hero">
        <div class="front-page-hero-inner">
            <div class="oc-grid">
                <div class="oc-copy">
                    <span class="front-page-eyebrow oc-eyebrow"><i class="fa fa-check-circle"></i>Order Confirmed</span>
                    <h1 class="front-page-title oc-title">The order is placed, the payment path is clear, and fulfilment can move next.</h1>
                    <p class="front-page-subtitle oc-subtitle">Order <strong>{{ $order->order_number }}</strong> was placed on {{ $placedAt }} with <strong>{{ $paymentMethodLabel }}</strong> selected as the payment method.</p>
                    <div class="oc-chip-row">
                        <span class="oc-chip"><i class="fa fa-receipt"></i>{{ $order->order_number }}</span>
                        <span class="oc-chip"><i class="fa fa-credit-card"></i>{{ $paymentMethodLabel }}</span>
                        <span class="oc-chip"><i class="fa fa-shopping-bag"></i>{{ $itemCount }} item{{ $itemCount === 1 ? '' : 's' }}</span>
                        @if ($order->shipping_eta)<span class="oc-chip"><i class="fa fa-truck"></i>{{ $order->shipping_eta }}</span>@endif
                    </div>
                    <div class="oc-actions">
                        <a href="#order-breakdown" class="oc-link oc-link--solid"><i class="fa fa-box-open"></i>Review order details</a>
                        <a href="{{ route('user.account', [], false) }}" class="oc-link oc-link--ghost"><i class="fa fa-user"></i>Manage account</a>
                    </div>
                    <div class="front-page-breadcrumb d-inline-flex flex-wrap mt-4">
                        <p class="m-0"><a href="{{ route('home', [], false) }}">Home</a></p><p class="m-0 px-2">/</p><p class="m-0">Order confirmation</p>
                    </div>
                </div>
                <div class="oc-panel">
                    <div class="oc-panel-top">
                        <div><span class="oc-panel-kicker">Purchase Status</span><h3>Everything important is visible right away.</h3></div>
                        <span class="oc-pill" data-tone="{{ $statusTone }}"><i class="fa {{ $statusTone === 'paid' ? 'fa-check' : ($statusTone === 'partial' ? 'fa-adjust' : 'fa-clock') }}"></i>{{ $statusLabel }}</span>
                    </div>
                    <div class="oc-stats">
                        <div class="oc-stat"><span class="oc-stat-label">Grand Total</span><span class="oc-stat-value">{{ $money((float) $order->grand_total) }}</span></div>
                        <div class="oc-stat"><span class="oc-stat-label">Items</span><span class="oc-stat-value">{{ $itemCount }}</span></div>
                        <div class="oc-stat"><span class="oc-stat-label">ETA</span><span class="oc-stat-value">{{ $order->shipping_eta ?: 'Pending' }}</span></div>
                        <div class="oc-stat"><span class="oc-stat-label">Zone</span><span class="oc-stat-value">{{ $order->shipping_zone ?: 'To confirm' }}</span></div>
                    </div>
                    <div class="oc-panel-note"><strong>{{ $statusCopy }}</strong><p>{{ $order->shipping_eta ? 'Estimated arrival in ' . $order->shipping_eta . ($order->shipping_zone ? ' via the ' . $order->shipping_zone . ' zone.' : '.') : 'Delivery timing will be confirmed as soon as the order enters fulfilment.' }}</p></div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid front-page-shell">
        <div class="row px-xl-5">
            <div class="col-lg-8 mb-5">
                @if (session('checkout_success'))
                    <div class="oc-alert"><strong class="d-block mb-1">Order update</strong>{{ session('checkout_success') }}</div>
                @endif

                <div class="oc-card">
                    <span class="oc-kicker">What Happens Next</span>
                    <h2 class="oc-heading">The page should answer the next question before the buyer asks it.</h2>
                    <p class="oc-copy-text">These next steps reflect the current payment method and keep the page useful beyond a simple receipt.</p>
                    <div class="oc-step-list">
                        @foreach ($nextSteps as $index => $step)
                            <div class="oc-step">
                                <span class="oc-step-no">{{ $index + 1 }}</span>
                                <div><div class="oc-step-title">{{ ['Order review', 'Fulfilment', 'Final handoff'][$index] ?? 'Next step' }}</div><div class="oc-meta">{{ $step }}</div></div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="oc-card" id="order-breakdown">
                    <span class="oc-kicker">Order Breakdown</span>
                    <h2 class="oc-heading">Every line from the placed order stays visible here.</h2>
                    <p class="oc-copy-text">This keeps the confirmation page useful as both reassurance and a clean record of what was actually submitted.</p>
                    <div class="oc-item-list">
                        @foreach ($order->items as $item)
                            <div class="oc-item">
                                <img src="{{ $item->product_image ?: asset('front/images/products/no-image.jpg') }}" alt="{{ $item->product_name }}">
                                <div>
                                    <div class="oc-item-top">
                                        <div>
                                            <div class="oc-item-name">{{ $item->product_name }}</div>
                                            @if (filled($item->product_code))<div class="oc-meta mt-1">Code: {{ $item->product_code }}</div>@endif
                                        </div>
                                        <div class="oc-item-price">{{ $money((float) $item->line_total) }}</div>
                                    </div>
                                    <div class="oc-tags">
                                        @if (filled($item->size) && strtoupper((string) $item->size) !== 'NA')<span class="oc-tag">Size: {{ $item->size }}</span>@endif
                                        @if (filled($item->color))<span class="oc-tag">Color: {{ $item->color }}</span>@endif
                                        <span class="oc-tag">Qty: {{ $item->quantity }}</span>
                                    </div>
                                    <div class="oc-meta">
                                        Unit price: {{ $money((float) $item->unit_price) }}
                                        @if (filled($item->product_url))
                                            <span class="mx-2">|</span><a href="{{ url($item->product_url) }}">View product</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="oc-card">
                    <span class="oc-kicker">Order Snapshot</span>
                    <div class="oc-side-title">Totals and settlement</div>
                    <div class="oc-total"><span>Subtotal</span><strong>{{ $money((float) $order->subtotal_amount) }}</strong></div>
                    <div class="oc-total"><span>Discount</span><strong>{{ (float) $order->discount_amount > 0 ? '-' . $money((float) $order->discount_amount) : $money(0) }}</strong></div>
                    @if ((float) $order->wallet_applied_amount > 0)
                        <div class="oc-total"><span>Wallet Applied</span><strong>-{{ $money((float) $order->wallet_applied_amount) }}</strong></div>
                    @endif
                    <div class="oc-total"><span>Shipping</span><strong>{{ (float) $order->shipping_amount > 0 ? $money((float) $order->shipping_amount) : 'Free' }}</strong></div>
                    <div class="oc-total oc-total--grand"><span>Grand Total</span><strong>{{ $money((float) $order->grand_total) }}</strong></div>
                </div>

                <div class="oc-card">
                    <span class="oc-kicker">Delivery Summary</span>
                    <div class="oc-side-title">Where this order is going</div>
                    <div class="oc-meta"><strong class="d-block text-dark mb-2">{{ $order->recipient_name }} | {{ $order->recipient_phone }}</strong>{{ $deliveryLine ?: 'Delivery details are being confirmed.' }}@if($deliveryMeta)<div class="mt-2">{{ $deliveryMeta }}</div>@endif</div>
                </div>

                <div class="oc-card">
                    <span class="oc-kicker">Need Anything After Checkout?</span>
                    <div class="oc-side-title">Keep the next actions obvious.</div>
                    <p class="oc-copy-text">The account area is the best place to review saved addresses, wallet history, and future orders after this purchase.</p>
                    <div class="oc-help oc-actions">
                        <a href="{{ route('user.orders.index', [], false) }}" class="oc-link oc-link--ghost"><i class="fa fa-user"></i>My Orders</a>
                        <a href="{{ route('home', [], false) }}" class="oc-link oc-link--solid"><i class="fa fa-arrow-left"></i>Continue shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
