@extends('front.layout.layout')

@php
    $user = auth()->user();
    $addresses = collect($addresses ?? [])->values();
    $countries = collect($countries ?? [])->filter(fn ($country) => filled(data_get($country, 'name')))->values();
    $selectedCountry = old('address_country', 'Kenya');
    $selectedCounty = old('address_county');
    $selectedSubCounty = old('address_sub_county');
    $selectedCountryRecord = $countries->first(fn ($country) => strcasecmp((string) data_get($country, 'name'), (string) $selectedCountry) === 0);

    if ($selectedCountry && ! $selectedCountryRecord) {
        $countries = $countries->prepend((object) ['id' => null, 'name' => $selectedCountry])->values();
    }

    $countries = $countries->unique(fn ($country) => strtolower((string) data_get($country, 'name')))->values();
    $addressCountryIsKenya = strcasecmp((string) $selectedCountry, 'Kenya') === 0;
    $makeDefaultChecked = (bool) old('make_default', $addresses->isEmpty());
    $checkoutCartItems = collect($summary['cartItems'] ?? []);
    $checkoutItemCount = (int) $checkoutCartItems->sum('qty');
    $checkoutAddressCount = $addresses->count();
    $checkoutGrandTotal = (float) ($summary['grandTotal'] ?? 0);
    $checkoutReady = (bool) ($summary['canProceed'] ?? false);
    $checkoutStatusMessage = (string) ($summary['statusMessage'] ?? '');
    $selectedAddressLabel = $selectedAddress?->label ?: 'Address pending';
    $selectedRecipient = $selectedAddress?->recipient_name ?: 'Choose recipient';
@endphp

@push('styles')
<style>
    .checkout-page-shell{padding-bottom:4rem}
    .checkout-page-hero{position:relative;overflow:hidden;border-bottom:0;background:
        radial-gradient(circle at 14% 16%,rgba(139,28,45,.18),transparent 28%),
        radial-gradient(circle at 84% 14%,rgba(15,106,102,.16),transparent 24%),
        linear-gradient(135deg,#fbf3ea 0%,#fffdf9 48%,#eff7f6 100%)}
    .checkout-page-hero::before,.checkout-page-hero::after{content:"";position:absolute;border-radius:999px;pointer-events:none}
    .checkout-page-hero::before{width:360px;height:360px;right:-110px;top:-130px;background:rgba(18,50,59,.05)}
    .checkout-page-hero::after{width:240px;height:240px;left:-80px;bottom:-105px;background:rgba(196,164,92,.12)}
    .checkout-page-hero .front-page-hero-inner{max-width:1240px;min-height:auto;padding:4.15rem 1rem 3.5rem;position:relative;z-index:1}
    .checkout-hero-grid{display:grid;grid-template-columns:minmax(0,1.32fr) minmax(320px,.98fr);gap:2rem;align-items:end}
    .checkout-hero-copy{max-width:720px}
    .checkout-hero-eyebrow{display:inline-flex;align-items:center;gap:.6rem;margin-bottom:1rem;padding:.62rem 1rem;border-radius:999px;background:rgba(255,255,255,.82);border:1px solid rgba(139,28,45,.12);box-shadow:0 12px 28px rgba(139,28,45,.08)}
    .checkout-hero-title{max-width:690px;margin-bottom:.9rem}
    .checkout-hero-subtitle{max-width:625px;margin:0;color:#5f6368}
    .checkout-hero-steps{display:flex;flex-wrap:wrap;gap:.75rem;margin:1.45rem 0 1.35rem}
    .checkout-hero-step{display:inline-flex;align-items:center;gap:.55rem;padding:.72rem .95rem;border-radius:999px;background:rgba(255,255,255,.72);border:1px solid rgba(18,50,59,.08);font-size:.88rem;font-weight:700;color:#314047}
    .checkout-hero-step.is-complete{background:rgba(15,106,102,.1);color:var(--front-luxe-accent)}
    .checkout-hero-step.is-active{background:var(--front-luxe-primary);color:#fff;box-shadow:0 16px 30px rgba(18,50,59,.16)}
    .checkout-page-hero .front-page-breadcrumb p,.checkout-page-hero .front-page-breadcrumb a{color:rgba(18,50,59,.78)}
    .checkout-hero-panel{position:relative;padding:1.55rem;border-radius:32px;background:rgba(18,50,59,.95);color:#fff;box-shadow:0 28px 54px rgba(18,50,59,.22);overflow:hidden}
    .checkout-hero-panel::before{content:"";position:absolute;inset:auto -14% -44% auto;width:240px;height:240px;border-radius:50%;background:radial-gradient(circle,rgba(196,164,92,.22),transparent 68%)}
    .checkout-hero-panel-top{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;margin-bottom:1rem}
    .checkout-hero-panel-kicker{display:block;font-size:.76rem;letter-spacing:.14rem;text-transform:uppercase;color:rgba(255,255,255,.68);font-weight:700}
    .checkout-hero-panel h3{font-family:"Cormorant Garamond",Georgia,serif;font-size:2rem;line-height:1;margin:.35rem 0 0}
    .checkout-hero-panel-pill{display:inline-flex;align-items:center;gap:.45rem;padding:.55rem .85rem;border-radius:999px;font-size:.82rem;font-weight:700;border:1px solid transparent}
    .checkout-hero-panel-pill[data-state=ready]{background:rgba(34,197,94,.14);border-color:rgba(34,197,94,.2);color:#bbf7d0}
    .checkout-hero-panel-pill[data-state=pending]{background:rgba(245,158,11,.12);border-color:rgba(245,158,11,.2);color:#fde68a}
    .checkout-hero-stat-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.8rem}
    .checkout-hero-stat{padding:1rem;border-radius:22px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.08)}
    .checkout-hero-stat-label{display:block;font-size:.74rem;letter-spacing:.12rem;text-transform:uppercase;color:rgba(255,255,255,.58);margin-bottom:.35rem}
    .checkout-hero-stat-value{display:block;font-size:1.18rem;font-weight:700;color:#fff;line-height:1.15}
    .checkout-hero-focus{margin-top:1rem;padding:1rem 1.05rem;border-radius:24px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.08)}
    .checkout-hero-focus-label{display:block;font-size:.74rem;letter-spacing:.12rem;text-transform:uppercase;color:rgba(255,255,255,.58);margin-bottom:.4rem}
    .checkout-hero-focus strong{display:block;font-size:1.1rem;line-height:1.2}
    .checkout-hero-focus p{margin:.55rem 0 0;color:rgba(255,255,255,.72);line-height:1.7}
    .checkout-card,.checkout-summary-shell,.checkout-address-card,.checkout-address-empty,.checkout-form-shell,.checkout-totals-card,.checkout-placeholder-card{background:var(--front-luxe-surface);border:1px solid rgba(139,28,45,.12);border-radius:28px;box-shadow:0 20px 44px rgba(26,26,26,.08)}
    .checkout-card{overflow:hidden}
    .checkout-panel-head,.checkout-summary-head{display:flex;justify-content:space-between;gap:1rem;padding:1.6rem;border-bottom:1px solid rgba(139,28,45,.08)}
    .checkout-kicker{display:inline-block;margin-bottom:.55rem;letter-spacing:.22rem;text-transform:uppercase;font-size:.74rem;font-weight:700;color:var(--front-luxe-accent)}
    .checkout-title{font-family:"Cormorant Garamond",Georgia,serif;font-size:clamp(2rem,3vw,2.6rem);line-height:1;margin-bottom:.45rem}
    .checkout-copy,.checkout-address-copy,.checkout-address-meta,.checkout-address-recipient{color:var(--front-luxe-muted);line-height:1.7}
    .checkout-chip{display:inline-flex;align-items:center;gap:.5rem;padding:.7rem .95rem;border-radius:999px;background:#fff;border:1px solid rgba(139,28,45,.12);font-size:.88rem;font-weight:600;white-space:nowrap}
    .checkout-chip i{color:var(--front-luxe-accent)}
    .checkout-panel-body{padding:1.4rem 1.6rem 1.6rem}
    .checkout-address-grid,.checkout-summary-items{display:grid;gap:1rem}
    .checkout-address-grid{margin-bottom:1.25rem}
    .checkout-address-card.is-selected{border-color:rgba(15,106,102,.36);box-shadow:0 12px 28px rgba(15,106,102,.12)}
    .checkout-address-card.is-default{background:linear-gradient(180deg,rgba(255,255,255,.98),rgba(246,240,231,.95))}
    .checkout-address-card,.checkout-totals-card,.checkout-placeholder-card{padding:1.1rem;background:#fff}
    .checkout-address-head,.checkout-summary-item,.checkout-summary-pricing,.checkout-total-row{display:flex;justify-content:space-between;gap:1rem}
    .checkout-address-head{align-items:flex-start;margin-bottom:.8rem}
    .checkout-address-label{font-size:1rem;font-weight:700}
    .checkout-badge-row,.checkout-address-actions,.checkout-form-actions{display:flex;flex-wrap:wrap;gap:.7rem}
    .checkout-address-actions{align-items:center}
    .checkout-badge-row{margin-bottom:.75rem}
    .checkout-badge{display:inline-flex;border-radius:999px;padding:.35rem .7rem;font-size:.74rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase}
    .checkout-badge[data-tone=default]{background:rgba(15,106,102,.12);color:var(--front-luxe-accent)}
    .checkout-badge[data-tone=missing]{background:rgba(180,83,9,.12);color:#b45309}
    .checkout-address-select,.checkout-submit-btn{min-height:48px;border-radius:999px;font-weight:700;padding:.7rem 1rem}
    .checkout-address-action{min-height:42px;border-radius:999px;font-size:.82rem;font-weight:700;padding:.6rem 1rem}
    .checkout-address-manage{display:inline-flex;align-items:center;font-size:.88rem;font-weight:600}
    .checkout-address-empty,.checkout-form-shell{background:rgba(15,106,102,.05);border-color:rgba(15,106,102,.1);color:var(--front-luxe-muted)}
    .checkout-form-shell{margin-top:1.25rem;padding:1.2rem}
    .checkout-form-shell.is-editing{background:rgba(196,164,92,.08);border-color:rgba(196,164,92,.2)}
    .checkout-form-title{font-family:"Cormorant Garamond",Georgia,serif;font-size:2rem;margin-bottom:.4rem}
    .checkout-field{margin-bottom:1rem}
    .checkout-label{display:block;margin-bottom:.55rem;font-size:.9rem;font-weight:600;color:var(--front-luxe-text)}
    .checkout-input,.checkout-select,.checkout-textarea{min-height:54px;padding:.9rem 1rem;border-radius:16px;border:1px solid rgba(18,50,59,.14);background:#fff;box-shadow:none}
    .checkout-textarea{min-height:118px;resize:vertical}
    .checkout-input:focus,.checkout-select:focus,.checkout-textarea:focus{border-color:rgba(15,106,102,.4);box-shadow:0 0 0 .2rem rgba(15,106,102,.12)}
    .checkout-helper,.checkout-location-status,.checkout-status{display:block;border-radius:14px;padding:.75rem .85rem;font-size:.84rem}
    .checkout-helper{background:rgba(196,164,92,.1);color:#7c5e17}
    .checkout-status{margin-bottom:1rem;border:1px solid transparent}
    .checkout-status[data-tone=success],.checkout-location-status[data-state=success]{background:rgba(21,128,61,.08);border-color:rgba(21,128,61,.12);color:#166534}
    .checkout-status[data-tone=danger],.checkout-location-status[data-state=error]{background:rgba(220,38,38,.08);border-color:rgba(220,38,38,.12);color:#b91c1c}
    .checkout-status[data-tone=warning],.checkout-status[data-tone=info],.checkout-location-status[data-state=info],.checkout-location-status[data-state=loading]{background:rgba(15,106,102,.07);border-color:rgba(15,106,102,.1);color:var(--front-luxe-accent)}
    .checkout-location-status{display:none;margin-top:.55rem}
    .checkout-location-status.is-visible{display:block}
    .checkout-checkbox{display:inline-flex;align-items:center;gap:.7rem;margin-top:.25rem}
    .checkout-checkbox input{width:1rem;height:1rem;margin:0}
    .checkout-summary-shell{position:sticky;top:1.5rem;overflow:hidden}
    .checkout-summary-shell.is-loading{pointer-events:none;opacity:.72}
    .checkout-summary-shell.is-loading::after{content:"Updating totals...";position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(255,249,240,.76);color:var(--front-luxe-accent);font-weight:700;z-index:2}
    .checkout-summary-item{align-items:flex-start}
    .checkout-summary-thumb{width:74px;height:92px;object-fit:cover;border-radius:18px;border:1px solid rgba(139,28,45,.12);background:#fff}
    .checkout-summary-item-copy{flex:1}
    .checkout-summary-pricing,.checkout-total-row{align-items:center}
    .checkout-totals-card,.checkout-placeholder-card{margin:1rem 0}
    .checkout-total-row{padding-bottom:.9rem;margin-bottom:.9rem;border-bottom:1px solid rgba(139,28,45,.08)}
    .checkout-total-row--grand{padding:0;margin:0;border:0;font-size:1.02rem}
    .checkout-toast-stack{position:fixed;top:1.25rem;right:1rem;z-index:1080;display:grid;gap:.75rem;width:min(360px,calc(100vw - 2rem))}
    .checkout-toast{border:1px solid rgba(18,50,59,.12);border-radius:18px;box-shadow:0 18px 36px rgba(15,23,42,.14);overflow:hidden}
    .checkout-toast .toast-header{background:#fff;border-bottom:1px solid rgba(18,50,59,.08)}
    .checkout-toast .toast-body{background:#fff;color:var(--front-luxe-text)}
    .checkout-toast[data-tone=success] .toast-header{background:rgba(21,128,61,.12);color:#166534}
    .checkout-toast[data-tone=danger] .toast-header{background:rgba(220,38,38,.12);color:#b91c1c}
    .checkout-toast[data-tone=info] .toast-header{background:rgba(15,106,102,.12);color:var(--front-luxe-accent)}
    .checkout-modal .modal-content{border:1px solid rgba(139,28,45,.12);border-radius:24px;box-shadow:0 20px 44px rgba(26,26,26,.12)}
    .checkout-modal .modal-header,.checkout-modal .modal-footer{border-color:rgba(139,28,45,.08)}
    .checkout-modal .modal-title{font-family:"Cormorant Garamond",Georgia,serif;font-size:1.9rem}
    .checkout-delete-copy{color:var(--front-luxe-muted);line-height:1.7}
    .checkout-page [data-location-mode][hidden]{display:none!important}
    @media (max-width:991.98px){.checkout-hero-grid{grid-template-columns:1fr}.checkout-summary-shell{position:static;margin-top:1.5rem}.checkout-panel-head,.checkout-summary-head{flex-direction:column}}
    @media (max-width:767.98px){.checkout-hero-stat-grid{grid-template-columns:1fr}}
    @media (max-width:575.98px){.checkout-toast-stack{left:1rem;right:1rem;width:auto}}
</style>
@endpush

@section('content')
<div class="front-luxe-page checkout-page">
    <div class="container-fluid bg-secondary mb-5 front-page-hero checkout-page-hero">
        <div class="front-page-hero-inner">
            <div class="checkout-hero-grid">
                <div class="checkout-hero-copy">
                    <span class="front-page-eyebrow checkout-hero-eyebrow">
                        <i class="fa fa-credit-card"></i>
                        Checkout Flow
                    </span>
                    <h1 class="front-page-title checkout-hero-title">Delivery, totals, and final payment steps now share one clear surface.</h1>
                    <p class="front-page-subtitle checkout-hero-subtitle">Select the right address, validate serviceability by pincode, and move into order placement with the summary and shipping state always visible.</p>

                    <div class="checkout-hero-steps">
                        <span class="checkout-hero-step is-complete">
                            <i class="fa fa-check-circle"></i>
                            Bag reviewed
                        </span>
                        <span class="checkout-hero-step is-active">
                            <i class="fa fa-map-marker-alt"></i>
                            Delivery details
                        </span>
                        <span class="checkout-hero-step">
                            <i class="fa fa-receipt"></i>
                            Place order
                        </span>
                    </div>

                    <div class="front-page-breadcrumb d-inline-flex flex-wrap">
                        <p class="m-0"><a href="{{ route('home', [], false) }}">Home</a></p>
                        <p class="m-0 px-2">/</p>
                        <p class="m-0">Checkout</p>
                    </div>
                </div>

                <div class="checkout-hero-panel">
                    <div class="checkout-hero-panel-top">
                        <div>
                            <span class="checkout-hero-panel-kicker">Current Status</span>
                            <h3>Keep delivery data tight before payment.</h3>
                        </div>
                        <span class="checkout-hero-panel-pill" data-state="{{ $checkoutReady ? 'ready' : 'pending' }}">
                            <i class="fa {{ $checkoutReady ? 'fa-check' : 'fa-exclamation-circle' }}"></i>
                            {{ $checkoutReady ? 'Ready to place order' : 'Needs attention' }}
                        </span>
                    </div>

                    <div class="checkout-hero-stat-grid">
                        <div class="checkout-hero-stat">
                            <span class="checkout-hero-stat-label">Saved Addresses</span>
                            <span class="checkout-hero-stat-value">{{ $checkoutAddressCount }}</span>
                        </div>
                        <div class="checkout-hero-stat">
                            <span class="checkout-hero-stat-label">Items in Order</span>
                            <span class="checkout-hero-stat-value">{{ $checkoutItemCount }}</span>
                        </div>
                        <div class="checkout-hero-stat">
                            <span class="checkout-hero-stat-label">Grand Total</span>
                            <span class="checkout-hero-stat-value">KSH.{{ number_format($checkoutGrandTotal, 2) }}</span>
                        </div>
                    </div>

                    <div class="checkout-hero-focus">
                        <span class="checkout-hero-focus-label">Selected Delivery Contact</span>
                        <strong>{{ $selectedAddressLabel }} | {{ $selectedRecipient }}</strong>
                        <p>{{ $checkoutStatusMessage }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid front-page-shell checkout-page-shell" data-checkout-root data-summary-url="{{ route('user.checkout.summary', [], false) }}" data-selected-address-id="{{ $selectedAddressId }}" data-address-count="{{ $addresses->count() }}">
        <div class="row px-xl-5">
            <div class="col-lg-7 mb-5">
                <div class="checkout-card">
                    <div class="checkout-panel-head">
                        <div>
                            <span class="checkout-kicker">Shipping Details</span>
                            <h2 class="checkout-title">Address selection comes first</h2>
                            <p class="checkout-copy">Every saved address shows the recipient, phone, pincode, and full delivery line so payment only unlocks once delivery details are complete.</p>
                        </div>
                        <span class="checkout-chip"><i class="fa fa-map-marker-alt"></i>Address + delivery check</span>
                    </div>
                    <div class="checkout-panel-body">
                        <div class="checkout-toast-stack" aria-live="polite" aria-atomic="true" data-toast-stack></div>
                        @if (session('checkout_success'))
                            <div class="alert alert-success checkout-inline-alert mb-3">{{ session('checkout_success') }}</div>
                        @endif
                        @if (session('checkout_notice'))
                            <div class="alert alert-info checkout-inline-alert mb-3">{{ session('checkout_notice') }}</div>
                        @endif
                        @if (session('checkout_error'))
                            <div class="alert alert-danger checkout-inline-alert mb-3">{{ session('checkout_error') }}</div>
                        @endif
                        <div id="checkoutAddressCards" data-address-list>
                            @include('front.checkout.partials.address_cards', ['addresses' => $addresses, 'selectedAddressId' => $selectedAddressId])
                        </div>
                        <div class="checkout-form-shell" data-address-form-shell>
                            <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between mb-3">
                                <div>
                                    <h3 class="checkout-form-title" data-address-form-title>Add a new address</h3>
                                    <p class="checkout-copy mb-0" data-address-form-copy>Capture the shipping recipient, full street details, and pincode here. The order summary previews delivery as soon as the pincode is checked.</p>
                                </div>
                                <span class="checkout-chip mt-2 mt-md-0" data-address-form-chip><i class="fa fa-plus-circle"></i>New shipping address</span>
                            </div>
                            <div id="checkoutFormFeedback" data-form-feedback></div>
                            <form method="POST" action="{{ route('user.checkout.addresses', [], false) }}" id="checkoutAddressForm" data-checkout-address-form data-store-action="{{ route('user.checkout.addresses', [], false) }}" data-counties-url="{{ route('user.account.locations.counties', [], false) }}" data-sub-counties-url="{{ route('user.account.locations.sub-counties', [], false) }}" data-selected-country="{{ $selectedCountry }}" data-selected-county="{{ $selectedCounty }}" data-selected-sub-county="{{ $selectedSubCounty }}" data-create-country="Kenya" data-create-full-name="{{ $user?->name }}" data-create-phone="{{ $user?->phone }}" novalidate>
                                @csrf
                                <input type="hidden" value="" data-editing-address-id>
                                <input type="hidden" value="" data-address-method>
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <div class="checkout-field">
                                            <label class="checkout-label" for="full_name">Recipient Name</label>
                                            <input type="text" id="full_name" name="full_name" class="form-control checkout-input @error('full_name', 'checkoutAddress') is-invalid @enderror" value="{{ old('full_name', $user?->name) }}" placeholder="Full name for delivery" required>
                                            <div class="invalid-feedback d-block" data-error-for="full_name">@error('full_name', 'checkoutAddress'){{ $message }}@enderror</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="checkout-field">
                                            <label class="checkout-label" for="phone">Phone Number</label>
                                            <input type="text" id="phone" name="phone" class="form-control checkout-input @error('phone', 'checkoutAddress') is-invalid @enderror" value="{{ old('phone', $user?->phone) }}" placeholder="+254 700 000 000" required>
                                            <div class="invalid-feedback d-block" data-error-for="phone">@error('phone', 'checkoutAddress'){{ $message }}@enderror</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-4">
                                        <div class="checkout-field">
                                            <label class="checkout-label" for="address_country">Country</label>
                                            <select id="address_country" name="address_country" class="form-control checkout-select @error('address_country', 'checkoutAddress') is-invalid @enderror" data-location-country required>
                                                @foreach ($countries as $country)
                                                    <option value="{{ data_get($country, 'name') }}" data-country-id="{{ data_get($country, 'id') }}" {{ strcasecmp((string) data_get($country, 'name'), (string) $selectedCountry) === 0 ? 'selected' : '' }}>{{ data_get($country, 'name') }}</option>
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback d-block" data-error-for="address_country">@error('address_country', 'checkoutAddress'){{ $message }}@enderror</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4" data-location-mode="kenya-county" {{ $addressCountryIsKenya ? '' : 'hidden' }}>
                                        <div class="checkout-field">
                                            <label class="checkout-label" for="address_county_select">County</label>
                                            <select id="address_county_select" class="form-control checkout-select @error('address_county', 'checkoutAddress') is-invalid @enderror" data-location-county-select data-location-name="address_county" {{ $addressCountryIsKenya ? 'name=address_county required' : 'disabled' }}>
                                                <option value="">Select a county</option>
                                            </select>
                                            <div class="invalid-feedback d-block" data-error-for="address_county">@error('address_county', 'checkoutAddress'){{ $message }}@enderror</div>
                                            <div class="checkout-location-status" data-county-status></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4" data-location-mode="global-county" {{ $addressCountryIsKenya ? 'hidden' : '' }}>
                                        <div class="checkout-field">
                                            <label class="checkout-label" for="address_county_text">State / Province</label>
                                            <input type="text" id="address_county_text" class="form-control checkout-input @error('address_county', 'checkoutAddress') is-invalid @enderror" value="{{ $addressCountryIsKenya ? '' : $selectedCounty }}" placeholder="State, province, or region" data-location-county-text data-location-name="address_county" data-manual-required="true" {{ $addressCountryIsKenya ? 'disabled' : 'name=address_county' }}>
                                            <div class="invalid-feedback d-block" data-error-for="address_county">@error('address_county', 'checkoutAddress'){{ $message }}@enderror</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4" data-location-mode="kenya-sub-county" {{ $addressCountryIsKenya ? '' : 'hidden' }}>
                                        <div class="checkout-field">
                                            <label class="checkout-label" for="address_sub_county_select">Sub-county</label>
                                            <select id="address_sub_county_select" class="form-control checkout-select @error('address_sub_county', 'checkoutAddress') is-invalid @enderror" data-location-sub-county-select data-location-name="address_sub_county" {{ $addressCountryIsKenya ? 'name=address_sub_county required' : 'disabled' }}>
                                                <option value="">Select a sub-county</option>
                                            </select>
                                            <div class="invalid-feedback d-block" data-error-for="address_sub_county">@error('address_sub_county', 'checkoutAddress'){{ $message }}@enderror</div>
                                            <div class="checkout-location-status" data-sub-county-status></div>
                                        </div>
                                    </div>
                                    <div class="col-md-4" data-location-mode="global-sub-county" {{ $addressCountryIsKenya ? 'hidden' : '' }}>
                                        <div class="checkout-field">
                                            <label class="checkout-label" for="address_sub_county_text">City / District / Area</label>
                                            <input type="text" id="address_sub_county_text" class="form-control checkout-input @error('address_sub_county', 'checkoutAddress') is-invalid @enderror" value="{{ $addressCountryIsKenya ? '' : $selectedSubCounty }}" placeholder="City, district, or area" data-location-sub-county-text data-location-name="address_sub_county" data-manual-required="true" {{ $addressCountryIsKenya ? 'disabled' : 'name=address_sub_county' }}>
                                            <div class="invalid-feedback d-block" data-error-for="address_sub_county">@error('address_sub_county', 'checkoutAddress'){{ $message }}@enderror</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-8">
                                        <div class="checkout-field">
                                            <label class="checkout-label" for="address_line1">House / Street</label>
                                            <input type="text" id="address_line1" name="address_line1" class="form-control checkout-input @error('address_line1', 'checkoutAddress') is-invalid @enderror" value="{{ old('address_line1') }}" placeholder="Building, street, or primary delivery line" required>
                                            <div class="invalid-feedback d-block" data-error-for="address_line1">@error('address_line1', 'checkoutAddress'){{ $message }}@enderror</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="checkout-field">
                                            <label class="checkout-label" for="address_pincode">Pincode</label>
                                            <input type="text" id="address_pincode" name="address_pincode" class="form-control checkout-input @error('address_pincode', 'checkoutAddress') is-invalid @enderror" value="{{ old('address_pincode') }}" placeholder="00100" inputmode="numeric" required>
                                            <div class="invalid-feedback d-block" data-error-for="address_pincode">@error('address_pincode', 'checkoutAddress'){{ $message }}@enderror</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="col-md-6">
                                        <div class="checkout-field">
                                            <label class="checkout-label" for="address_line2">Address Line 2</label>
                                            <input type="text" id="address_line2" name="address_line2" class="form-control checkout-input" value="{{ old('address_line2') }}" placeholder="Apartment, suite, floor, or extra detail">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="checkout-field">
                                            <label class="checkout-label" for="address_estate">Estate / Neighborhood</label>
                                            <input type="text" id="address_estate" name="address_estate" class="form-control checkout-input" value="{{ old('address_estate') }}" placeholder="Kilimani, Karen, Nyali, or neighborhood">
                                        </div>
                                    </div>
                                </div>
                                <div class="checkout-field">
                                    <label class="checkout-label" for="address_landmark">Landmark</label>
                                    <textarea id="address_landmark" name="address_landmark" class="form-control checkout-textarea" placeholder="Opposite the mall, next to the stage, or another useful landmark">{{ old('address_landmark') }}</textarea>
                                </div>
                                <div class="checkout-helper">Use the pincode check before saving to see whether delivery is available and how the order summary will change.</div>
                                <div class="checkout-location-status {{ old('address_pincode') ? 'is-visible' : '' }}" data-pincode-status data-state="info"></div>
                                <label class="checkout-checkbox">
                                    <input type="checkbox" name="make_default" value="1" {{ $makeDefaultChecked ? 'checked' : '' }} data-address-default-checkbox>
                                    <span>Set this as the default delivery address for future checkouts.</span>
                                </label>
                                <div class="checkout-form-actions">
                                    <button type="button" class="btn btn-outline-secondary checkout-submit-btn" data-pincode-check>Check pincode</button>
                                    <button type="button" class="btn btn-outline-dark checkout-submit-btn d-none" data-address-cancel>Cancel edit</button>
                                    <button type="submit" class="btn btn-primary checkout-submit-btn" data-address-submit>Save address</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div id="checkoutSummaryPanel" class="checkout-summary-shell" data-summary-shell>
                    @include('front.checkout.partials.order_summary', $summary)
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade checkout-modal" id="checkoutDeleteModal" tabindex="-1" role="dialog" aria-labelledby="checkoutDeleteModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="checkoutDeleteModalTitle">Delete address</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Remove <strong data-delete-label>this saved address</strong> from checkout?</p>
                    <p class="checkout-delete-copy mb-0">If it is currently selected, checkout will automatically fall back to another saved address. Removing the last address will disable payment until a new one is added.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" data-delete-confirm>Delete address</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('front/js/checkout.js') }}"></script>
@endpush
