@extends('front.layout.layout')

@php
    $cartItemsCollection = collect($cartItems ?? []);
    $cartLineCount = $cartItemsCollection->count();
    $cartItemCount = (int) $cartItemsCollection->sum('qty');
    $cartSubtotal = (float) ($subtotal ?? 0);
    $cartWalletApplied = (float) ($wallet_applied ?? 0);
    $cartTotal = (float) ($total ?? 0);
@endphp

@push('styles')
<style>
    .cart-page-hero{position:relative;overflow:hidden;border-bottom:0;background:
        radial-gradient(circle at 12% 18%,rgba(196,164,92,.24),transparent 30%),
        radial-gradient(circle at 88% 14%,rgba(15,106,102,.16),transparent 26%),
        linear-gradient(135deg,#f8efe4 0%,#fffaf4 52%,#edf6f5 100%)}
    .cart-page-hero::before,.cart-page-hero::after{content:"";position:absolute;border-radius:999px;pointer-events:none;animation:cartHeroFloat 16s ease-in-out infinite alternate}
    .cart-page-hero::before{width:320px;height:320px;top:-120px;right:-80px;background:rgba(139,28,45,.07)}
    .cart-page-hero::after{width:220px;height:220px;bottom:-90px;left:-70px;background:rgba(15,106,102,.09);animation-duration:19s}
    .cart-page-hero .front-page-hero-inner{max-width:1240px;min-height:auto;padding:4.15rem 1rem 3.5rem;position:relative;z-index:1}
    .cart-hero-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,.95fr);gap:2rem;align-items:end}
    .cart-hero-copy{max-width:720px}
    .cart-hero-eyebrow{display:inline-flex;align-items:center;gap:.6rem;margin-bottom:1rem;padding:.62rem 1rem;border-radius:999px;background:rgba(255,255,255,.82);border:1px solid rgba(139,28,45,.12);box-shadow:0 12px 28px rgba(139,28,45,.08)}
    .cart-hero-title{max-width:680px;margin-bottom:.9rem}
    .cart-hero-subtitle{max-width:620px;margin:0;color:#5f6368}
    .cart-hero-mini{display:flex;flex-wrap:wrap;gap:.75rem;margin:1.35rem 0 1.5rem}
    .cart-hero-mini span{display:inline-flex;align-items:center;gap:.45rem;padding:.7rem .95rem;border-radius:999px;background:rgba(255,255,255,.72);border:1px solid rgba(18,50,59,.08);font-size:.88rem;color:#314047}
    .cart-hero-mini strong{font-size:.95rem;color:var(--front-luxe-text)}
    .cart-hero-actions{display:flex;flex-wrap:wrap;gap:.85rem;margin-bottom:1.35rem}
    .cart-hero-link{display:inline-flex;align-items:center;gap:.6rem;min-height:50px;padding:.8rem 1.2rem;border-radius:999px;font-weight:700;transition:transform .2s ease,box-shadow .2s ease}
    .cart-hero-link:hover{transform:translateY(-1px);text-decoration:none}
    .cart-hero-link--solid{background:var(--front-luxe-primary);color:#fff;box-shadow:0 16px 32px rgba(18,50,59,.18)}
    .cart-hero-link--ghost{background:rgba(255,255,255,.72);color:var(--front-luxe-text);border:1px solid rgba(18,50,59,.1)}
    .cart-hero-breadcrumb{justify-content:flex-start}
    .cart-page-hero .front-page-breadcrumb p,.cart-page-hero .front-page-breadcrumb a{color:rgba(18,50,59,.78)}
    .cart-hero-panel{position:relative;padding:1.45rem;border-radius:30px;background:rgba(255,255,255,.78);border:1px solid rgba(139,28,45,.14);box-shadow:0 24px 46px rgba(26,26,26,.1);backdrop-filter:blur(14px);overflow:hidden}
    .cart-hero-panel::after{content:"";position:absolute;inset:auto -12% -42% auto;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(196,164,92,.18),transparent 68%)}
    .cart-hero-panel-head{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;margin-bottom:1rem}
    .cart-hero-panel-kicker{display:block;font-size:.76rem;letter-spacing:.14rem;text-transform:uppercase;color:var(--front-luxe-accent);font-weight:700}
    .cart-hero-panel h3{font-family:"Cormorant Garamond",Georgia,serif;font-size:2rem;line-height:1;margin:.35rem 0 0}
    .cart-hero-panel-chip{display:inline-flex;align-items:center;gap:.45rem;padding:.55rem .85rem;border-radius:999px;background:rgba(15,106,102,.1);color:var(--front-luxe-accent);font-size:.82rem;font-weight:700}
    .cart-hero-stats{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.85rem}
    .cart-hero-stat{padding:1rem;border-radius:22px;background:rgba(255,255,255,.86);border:1px solid rgba(18,50,59,.08)}
    .cart-hero-stat-label{display:block;font-size:.75rem;letter-spacing:.12rem;text-transform:uppercase;color:#6b7280;margin-bottom:.35rem}
    .cart-hero-stat-value{display:block;font-size:1.28rem;font-weight:700;color:var(--front-luxe-text);line-height:1.1}
    .cart-hero-note{margin-top:1rem;padding:1rem 1.05rem;border-radius:24px;background:rgba(18,50,59,.05);color:#4b5563;line-height:1.7}
    @keyframes cartHeroFloat{from{transform:translate3d(0,0,0)}to{transform:translate3d(18px,-16px,0)}}
    @media (max-width:991.98px){
        .cart-hero-grid{grid-template-columns:1fr}
        .cart-hero-panel{max-width:620px}
    }
    @media (max-width:575.98px){
        .cart-page-hero .front-page-hero-inner{padding:3.3rem 1rem 2.8rem}
        .cart-hero-actions{flex-direction:column}
        .cart-hero-link{justify-content:center}
        .cart-hero-stats{grid-template-columns:1fr}
    }
</style>
@endpush

@section('content')
<div class="front-luxe-page cart-page">
    <div class="container-fluid bg-secondary mb-5 front-page-hero cart-page-hero">
        <div class="front-page-hero-inner">
            <div class="cart-hero-grid">
                <div class="cart-hero-copy">
                    <span class="front-page-eyebrow cart-hero-eyebrow">
                        <i class="fa fa-shopping-bag"></i>
                        Bag Review
                    </span>
                    <h1 class="front-page-title cart-hero-title">Shape the final mix before you move into checkout.</h1>
                    <p class="front-page-subtitle cart-hero-subtitle">Review variants, tune quantities, apply wallet credit, and carry a cleaner order summary into the next step.</p>

                    <div class="cart-hero-mini">
                        <span><strong>{{ $cartItemCount }}</strong> items</span>
                        <span><strong>{{ $cartLineCount }}</strong> selections</span>
                        <span><strong>KSH.{{ number_format($cartTotal, 2) }}</strong> payable now</span>
                    </div>

                    <div class="cart-hero-actions">
                        <a href="{{ route('home', [], false) }}" class="cart-hero-link cart-hero-link--ghost">
                            <i class="fa fa-arrow-left"></i>
                            Continue shopping
                        </a>
                        <a href="{{ route('user.checkout.index', [], false) }}" class="cart-hero-link cart-hero-link--solid">
                            Proceed to checkout
                            <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>

                    <div class="front-page-breadcrumb d-inline-flex flex-wrap cart-hero-breadcrumb">
                        <p class="m-0"><a href="{{ route('home', [], false) }}">Home</a></p>
                        <p class="m-0 px-2">/</p>
                        <p class="m-0">Shopping Cart</p>
                    </div>
                </div>

                <div class="cart-hero-panel">
                    <div class="cart-hero-panel-head">
                        <div>
                            <span class="cart-hero-panel-kicker">Bag Snapshot</span>
                            <h3>Everything visible at a glance.</h3>
                        </div>
                        <span class="cart-hero-panel-chip">
                            <i class="fa fa-lock"></i>
                            Checkout ready
                        </span>
                    </div>

                    <div class="cart-hero-stats">
                        <div class="cart-hero-stat">
                            <span class="cart-hero-stat-label">Subtotal</span>
                            <span class="cart-hero-stat-value">KSH.{{ number_format($cartSubtotal, 2) }}</span>
                        </div>
                        <div class="cart-hero-stat">
                            <span class="cart-hero-stat-label">Wallet Applied</span>
                            <span class="cart-hero-stat-value">{{ $cartWalletApplied > 0 ? 'KSH.' . number_format($cartWalletApplied, 2) : 'Not used' }}</span>
                        </div>
                        <div class="cart-hero-stat">
                            <span class="cart-hero-stat-label">Bag Lines</span>
                            <span class="cart-hero-stat-value">{{ $cartLineCount }}</span>
                        </div>
                        <div class="cart-hero-stat">
                            <span class="cart-hero-stat-label">Current Total</span>
                            <span class="cart-hero-stat-value">KSH.{{ number_format($cartTotal, 2) }}</span>
                        </div>
                    </div>

                    <div class="cart-hero-note">
                        Use this page to resolve size, color, coupon, and wallet decisions first. The checkout page can then stay focused on delivery details and final confirmation.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid front-page-shell pb-5">
        <div class="row px-xl-5">
            <div class="col-lg-8 mb-5">
                <div class="cart-table-card">
                    <div class="cart-section-head d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                        <div>
                            <span class="cart-section-kicker">Cart Review</span>
                            <h2 class="cart-section-title">Adjust your order before checkout</h2>
                            <p class="cart-section-copy mb-0">Update quantities, remove items, and keep the bag aligned with your preferred size and color mix.</p>
                        </div>
                        <span class="cart-inline-note">
                            <i class="fa fa-lock"></i>
                            Secure checkout ready
                        </span>
                    </div>

                    <div class="table-responsive cart-table-wrapper">
                        <table class="table table-bordered text-center mb-0 cart-table">
                            <thead class="bg-secondary text-dark">
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Remove</th>
                                </tr>
                            </thead>
                            <tbody class="align-middle" id="appendCartItems">
                                @include('front.cart.ajax_cart_items')
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-secondary mb-4 cart-summary-card">
                    <div class="card-header bg-secondary border-0 cart-summary-header">
                        <h4 class="font-weight-semi-bold m-0">Cart Summary</h4>
                    </div>
                    <div id="appendCartSummary">
                        @include('front.cart.ajax_cart_summary')
                    </div>
                </div>

                <div class="cart-support-card">
                    <div class="cart-support-icon">
                        <i class="fa fa-headset"></i>
                    </div>
                    <h5>Need help before checkout?</h5>
                    <p class="mb-0">Use this review step to confirm the right variant, then proceed once your bag feels complete.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
