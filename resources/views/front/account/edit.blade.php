@extends('front.layout.layout')

@php
    $savedAddresses = collect($savedAddresses ?? [])->values();
    $walletBalance = (float) ($walletBalance ?? 0);
    $walletEntries = collect($walletEntries ?? [])->values();
    $selectedCountry = old('country', $user->country ?: 'Kenya');
    $selectedCounty = old('county', $user->county);
    $selectedSubCounty = old('sub_county', $user->sub_county);
    $currentEmail = $user->email;
    $pendingEmail = $user->pending_email;
    $emailInputValue = old('email', $pendingEmail ?: $currentEmail);
    $emailVerified = filled($user->email_verified_at);
    $countries = collect($countries)->filter(fn ($country) => filled(data_get($country, 'name')))->values();
    $selectedCountryRecord = $countries->first(fn ($country) => strcasecmp((string) data_get($country, 'name'), (string) $selectedCountry) === 0);
    $editingAddressId = old('editing_address_id');

    if ($selectedCountry && ! $selectedCountryRecord) {
        $countries = $countries->prepend((object) ['id' => null, 'name' => $selectedCountry])->values();
    }

    $editingAddress = filled($editingAddressId)
        ? $savedAddresses->firstWhere('id', (int) $editingAddressId)
        : null;

    $savedAddressCountry = old('saved_address_country', $editingAddress?->country ?: 'Kenya');
    $savedAddressCounty = old('saved_address_county', $editingAddress?->county);
    $savedAddressSubCounty = old('saved_address_sub_county', $editingAddress?->sub_county);
    $savedAddressFullName = old('saved_address_full_name', $editingAddress?->full_name ?: $user->name);
    $savedAddressPhone = old('saved_address_phone', $editingAddress?->phone ?: $user->phone);
    $savedAddressPincode = old('saved_address_pincode', $editingAddress?->pincode);
    $savedAddressCountryRecord = $countries->first(fn ($country) => strcasecmp((string) data_get($country, 'name'), (string) $savedAddressCountry) === 0);

    if ($savedAddressCountry && ! $savedAddressCountryRecord) {
        $countries = $countries->prepend((object) ['id' => null, 'name' => $savedAddressCountry])->values();
    }

    $countries = $countries
        ->unique(fn ($country) => strtolower((string) data_get($country, 'name')))
        ->values();

    $isKenyaSelected = strcasecmp((string) $selectedCountry, 'Kenya') === 0;
    $isEditingAddress = $editingAddress !== null;
    $savedAddressIsKenya = strcasecmp((string) $savedAddressCountry, 'Kenya') === 0;
    $savedAddressFormAction = $isEditingAddress
        ? route('user.account.addresses.update', ['address' => $editingAddress->id], false)
        : route('user.account.addresses.store', [], false);
    $savedAddressShouldBeDefault = (bool) old(
        'saved_address_make_default',
        $isEditingAddress ? $editingAddress->is_default : $savedAddresses->isEmpty()
    );
@endphp

@push('styles')
<style>
    .account-page-shell {
        padding-bottom: 4rem;
    }

    .account-shell {
        background: linear-gradient(135deg, rgba(228, 211, 173, 0.14), rgba(15, 95, 115, 0.08));
        border: 1px solid rgba(15, 95, 115, 0.12);
        border-radius: 28px;
        box-shadow: 0 24px 54px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .account-aside {
        background:
            radial-gradient(circle at top, rgba(196, 164, 92, 0.22), transparent 48%),
            linear-gradient(180deg, #0f5f73 0%, #12323b 100%);
        color: #f7f3e8;
        height: 100%;
        padding: 2.5rem 2.25rem;
    }

    .account-kicker,
    .account-panel-kicker {
        color: #c4a45c;
        display: inline-block;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.18em;
        margin-bottom: 1rem;
        text-transform: uppercase;
    }

    .account-title,
    .account-panel-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: clamp(2rem, 3vw, 2.85rem);
        font-weight: 700;
        line-height: 1.05;
        margin-bottom: 1rem;
    }

    .account-copy {
        color: rgba(247, 243, 232, 0.82);
        font-size: 1rem;
        line-height: 1.75;
        margin-bottom: 1.5rem;
    }

    .account-stat-grid {
        display: grid;
        gap: 0.85rem;
        margin-bottom: 1.5rem;
    }

    .account-stat-card {
        background: rgba(255, 255, 255, 0.09);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 18px;
        padding: 1rem 1.1rem;
    }

    .account-stat-label {
        color: rgba(247, 243, 232, 0.7);
        display: block;
        font-size: 0.78rem;
        letter-spacing: 0.08em;
        margin-bottom: 0.4rem;
        text-transform: uppercase;
    }

    .account-stat-value {
        color: #fff;
        font-weight: 600;
    }

    .account-address-preview {
        background: rgba(7, 30, 35, 0.38);
        border-radius: 18px;
        padding: 1rem 1.1rem;
    }

    .account-panel {
        background: #fffdf7;
        height: 100%;
        padding: 2.5rem 2.25rem;
    }

    .account-panel-copy {
        color: #6c6f79;
        margin-bottom: 1.75rem;
    }

    .account-field {
        margin-bottom: 1.2rem;
    }

    .account-label {
        color: #12323b;
        display: block;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.55rem;
    }

    .account-input,
    .account-select,
    .account-textarea {
        background: #fff;
        border: 1px solid rgba(18, 50, 59, 0.14);
        border-radius: 16px;
        box-shadow: none;
        min-height: 54px;
        padding: 0.9rem 1rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    }

    .account-textarea {
        min-height: 120px;
        resize: vertical;
    }

    .account-input:focus,
    .account-select:focus,
    .account-textarea:focus {
        border-color: rgba(15, 95, 115, 0.45);
        box-shadow: 0 0 0 0.2rem rgba(15, 95, 115, 0.12);
        transform: translateY(-1px);
    }

    .account-helper {
        color: #6c6f79;
        display: block;
        font-size: 0.82rem;
        margin-top: 0.5rem;
    }

    .account-submit-btn {
        border-radius: 999px;
        font-weight: 700;
        letter-spacing: 0.04em;
        min-height: 56px;
    }

    .account-county-note {
        background: rgba(15, 95, 115, 0.06);
        border-radius: 14px;
        color: #0f5f73;
        font-size: 0.84rem;
        margin-top: 0.65rem;
        padding: 0.75rem 0.85rem;
    }

    .account-status-strip {
        align-items: center;
        background: rgba(15, 95, 115, 0.08);
        border: 1px solid rgba(15, 95, 115, 0.12);
        border-radius: 18px;
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1.35rem;
        padding: 0.95rem 1rem;
    }

    .account-status-badge {
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        padding: 0.45rem 0.75rem;
        text-transform: uppercase;
    }

    .account-status-badge[data-tone="success"] {
        background: rgba(21, 128, 61, 0.14);
        color: #166534;
    }

    .account-status-badge[data-tone="warning"] {
        background: rgba(180, 83, 9, 0.14);
        color: #b45309;
    }

    .account-inline-note,
    .account-location-status {
        border-radius: 14px;
        font-size: 0.83rem;
        margin-top: 0.65rem;
        padding: 0.75rem 0.85rem;
    }

    .account-inline-note {
        background: rgba(196, 164, 92, 0.1);
        color: #7c5e17;
    }

    .account-location-status {
        border: 1px solid rgba(15, 95, 115, 0.12);
        display: none;
    }

    .account-location-status.is-visible {
        display: block;
    }

    .account-location-status[data-state="loading"] {
        background: rgba(15, 95, 115, 0.06);
        color: #0f5f73;
    }

    .account-location-status[data-state="success"] {
        background: rgba(21, 128, 61, 0.08);
        color: #166534;
    }

    .account-location-status[data-state="error"] {
        background: rgba(220, 38, 38, 0.08);
        color: #b91c1c;
    }

    .account-divider {
        background: rgba(18, 50, 59, 0.08);
        height: 1px;
        margin: 2rem 0;
        width: 100%;
    }

    .account-section-title {
        color: #12323b;
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.9rem;
        font-weight: 700;
        line-height: 1.1;
        margin-bottom: 0.65rem;
    }

    .account-section-copy {
        color: #6c6f79;
        margin-bottom: 1.4rem;
    }

    .account-inline-form {
        margin-top: 0.85rem;
    }

    .account-inline-link {
        background: transparent;
        border: 0;
        color: #0f5f73;
        font-size: 0.86rem;
        font-weight: 700;
        padding: 0;
    }

    .account-inline-link:hover,
    .account-inline-link:focus {
        color: #12323b;
        text-decoration: underline;
    }

    [data-location-mode][hidden] {
        display: none !important;
    }

    .address-book-grid {
        display: grid;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .address-book-card {
        background: #fff;
        border: 1px solid rgba(18, 50, 59, 0.12);
        border-radius: 22px;
        box-shadow: 0 18px 30px rgba(15, 23, 42, 0.04);
        padding: 1.15rem 1.2rem;
    }

    .address-book-card.is-default {
        border-color: rgba(15, 95, 115, 0.28);
        box-shadow: 0 18px 34px rgba(15, 95, 115, 0.08);
    }

    .address-book-head {
        align-items: flex-start;
        display: flex;
        gap: 0.85rem;
        justify-content: space-between;
        margin-bottom: 0.75rem;
    }

    .address-book-label {
        color: #12323b;
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }

    .address-book-copy,
    .address-book-meta {
        color: #6c6f79;
        font-size: 0.92rem;
        line-height: 1.65;
    }

    .address-book-meta {
        margin-bottom: 0.9rem;
    }

    .address-book-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
    }

    .address-book-action {
        border-radius: 999px;
        font-size: 0.82rem;
        font-weight: 700;
        min-height: 42px;
        padding: 0.6rem 1rem;
    }

    .address-book-empty,
    .address-book-form-shell {
        background: rgba(15, 95, 115, 0.05);
        border: 1px solid rgba(15, 95, 115, 0.1);
        border-radius: 22px;
        padding: 1.25rem 1.2rem;
    }

    .address-book-form-shell {
        margin-top: 1rem;
    }

    .address-book-empty {
        color: #0f5f73;
        margin-bottom: 1.25rem;
    }

    .account-checkbox {
        align-items: center;
        display: inline-flex;
        gap: 0.7rem;
        margin-top: 0.25rem;
    }

    .account-checkbox input {
        height: 1rem;
        margin: 0;
        width: 1rem;
    }

    .account-button-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        margin-top: 0.35rem;
    }

    .wallet-history-list {
        display: grid;
        gap: 0.9rem;
    }

    .wallet-history-item {
        align-items: flex-start;
        background: rgba(15, 95, 115, 0.05);
        border: 1px solid rgba(15, 95, 115, 0.1);
        border-radius: 18px;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        padding: 1rem 1.05rem;
    }

    .wallet-history-meta {
        color: #6c6f79;
        font-size: 0.86rem;
        margin-top: 0.35rem;
    }

    .wallet-history-description {
        color: #12323b;
        font-weight: 600;
        line-height: 1.55;
        margin-top: 0.45rem;
    }

    .wallet-history-amount {
        font-weight: 700;
        text-align: right;
        white-space: nowrap;
    }

    .wallet-history-amount.is-credit {
        color: #15803d;
    }

    .wallet-history-amount.is-debit {
        color: #b91c1c;
    }

    .wallet-history-status {
        display: inline-flex;
        gap: 0.45rem;
        margin-bottom: 0.5rem;
    }

    .wallet-history-empty {
        background: rgba(15, 95, 115, 0.05);
        border: 1px solid rgba(15, 95, 115, 0.1);
        border-radius: 18px;
        color: #0f5f73;
        padding: 1rem 1.05rem;
    }

    @media (max-width: 991.98px) {
        .account-aside,
        .account-panel {
            padding: 2rem 1.5rem;
        }

        .address-book-head {
            align-items: stretch;
            flex-direction: column;
        }
    }
</style>
@endpush

@section('content')
<div class="front-luxe-page account-page">
    <div class="container-fluid bg-secondary mb-5 front-page-hero auth-page-hero">
        <div class="front-page-hero-inner d-flex flex-column align-items-center justify-content-center text-center">
            <span class="front-page-eyebrow">My Account</span>
            <h1 class="front-page-title mb-3">Keep your profile ready for every order and inquiry</h1>
            <p class="front-page-subtitle mb-3">Update your contact details and full delivery address in one place. Kenya now uses live county and sub-county dropdowns, while other countries switch to manual state and city fields.</p>
            <div class="front-page-breadcrumb d-inline-flex flex-wrap justify-content-center">
                <p class="m-0"><a href="{{ route('home', [], false) }}">Home</a></p>
                <p class="m-0 px-2">/</p>
                <p class="m-0">My Account</p>
            </div>
        </div>
    </div>

    <div class="container-fluid front-page-shell account-page-shell">
        <div class="row px-xl-5 justify-content-center">
            <div class="col-xl-11">
                <div class="account-shell">
                    <div class="row no-gutters">
                        <div class="col-lg-4">
                            <aside class="account-aside">
                                <span class="account-kicker">Account Snapshot</span>
                                <h2 class="account-title">A profile that stays checkout-ready.</h2>
                                <p class="account-copy">Keep your email, phone, and location current so support, delivery, and future purchases move without back-and-forth.</p>

                                <div class="account-stat-grid">
                                    <div class="account-stat-card">
                                        <span class="account-stat-label">Account Type</span>
                                        <span class="account-stat-value">{{ $user->user_type ?? 'Customer' }}</span>
                                    </div>

                                    <div class="account-stat-card">
                                        <span class="account-stat-label">Current Country</span>
                                        <span class="account-stat-value">{{ $user->country ?: 'Kenya' }}</span>
                                    </div>

                                    <div class="account-stat-card">
                                        <span class="account-stat-label">Phone</span>
                                        <span class="account-stat-value">{{ $user->phone ?: 'Add a contact number' }}</span>
                                    </div>

                                    <div class="account-stat-card">
                                        <span class="account-stat-label">Wallet Balance</span>
                                        <span class="account-stat-value">KSH.{{ number_format($walletBalance, 2) }}</span>
                                    </div>

                                    <div class="account-stat-card">
                                        <span class="account-stat-label">Email Status</span>
                                        <span class="account-stat-value">
                                            {{ $emailVerified ? 'Verified' : 'Verification pending' }}
                                            @if ($pendingEmail)
                                                <br><small>Change queued for {{ $pendingEmail }}</small>
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <div class="account-address-preview">
                                    <span class="account-stat-label">Saved Address</span>
                                    <div class="account-stat-value">
                                        {{ $user->full_address ?: 'No address saved yet. Fill the form to keep delivery details ready.' }}
                                    </div>
                                </div>
                            </aside>
                        </div>

                        <div class="col-lg-8">
                            <div class="account-panel">
                                <span class="account-panel-kicker">Profile Details</span>
                                <h3 class="account-panel-title">My Account</h3>
                                <p class="account-panel-copy">Your address fields map directly to the current user profile schema: address lines, country, county, sub-county, estate, landmark, phone, and email.</p>

                                @if (session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif

                                @if (session('info'))
                                    <div class="alert alert-info">{{ session('info') }}</div>
                                @endif

                                @if (session('password_success'))
                                    <div class="alert alert-success">{{ session('password_success') }}</div>
                                @endif

                                @if (session('address_success'))
                                    <div class="alert alert-success">{{ session('address_success') }}</div>
                                @endif

                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        Please correct the highlighted fields and try again.
                                    </div>
                                @endif

                                @if ($pendingEmail)
                                    <div class="account-inline-note mb-4">
                                        Verification for <strong>{{ $pendingEmail }}</strong> is still pending. Use the button below if you need a fresh link.
                                        <form method="POST" action="{{ route('user.account.email.resend', [], false) }}" class="account-inline-form">
                                            @csrf
                                            <button type="submit" class="account-inline-link">Resend verification email</button>
                                        </form>
                                    </div>
                                @endif

                                <form
                                    method="POST"
                                    action="{{ route('user.account.update', [], false) }}"
                                    novalidate
                                    data-account-location-form
                                    data-counties-url="{{ route('user.account.locations.counties', [], false) }}"
                                    data-sub-counties-url="{{ route('user.account.locations.sub-counties', [], false) }}"
                                    data-selected-county="{{ $selectedCounty }}"
                                    data-selected-sub-county="{{ $selectedSubCounty }}"
                                >
                                    @csrf
                                    @method('PUT')

                                    <div class="account-status-strip">
                                        <span class="account-status-badge" data-tone="{{ $emailVerified ? 'success' : 'warning' }}">
                                            {{ $emailVerified ? 'Email Verified' : 'Email Unverified' }}
                                        </span>
                                        <span class="small text-muted">
                                            Current login email: <strong>{{ $currentEmail }}</strong>
                                            @if ($pendingEmail)
                                                <br>Pending change awaiting verification: <strong>{{ $pendingEmail }}</strong>
                                            @endif
                                        </span>
                                    </div>

                                    <div class="form-row">
                                        <div class="col-md-6">
                                            <div class="account-field">
                                                <label class="account-label" for="name">Full Name</label>
                                                <input type="text" id="name" name="name" class="form-control account-input @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" placeholder="Your full name" required>
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="account-field">
                                                <label class="account-label" for="email">Email Address</label>
                                                <input type="email" id="email" name="email" class="form-control account-input @error('email') is-invalid @enderror" value="{{ $emailInputValue }}" placeholder="you@example.com" required>
                                                @if ($pendingEmail)
                                                    <div class="account-inline-note">
                                                        Your login email stays <strong>{{ $currentEmail }}</strong> until you verify <strong>{{ $pendingEmail }}</strong>.
                                                    </div>
                                                @else
                                                    <small class="account-helper">If you enter a new email, we will send a verification link before it becomes your login email.</small>
                                                @endif
                                                @error('email')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="col-md-6">
                                            <div class="account-field">
                                                <label class="account-label" for="phone">Phone Number</label>
                                                <input type="text" id="phone" name="phone" class="form-control account-input @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" placeholder="+254 700 000 000">
                                                @error('phone')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="account-field">
                                                <label class="account-label" for="profile_country">Country</label>
                                                <select id="profile_country" name="country" class="form-control account-select @error('country') is-invalid @enderror" data-location-country required>
                                                    <option value="">Select a country</option>
                                                    @foreach ($countries as $country)
                                                        @php
                                                            $countryName = (string) data_get($country, 'name');
                                                            $countryId = data_get($country, 'id');
                                                        @endphp
                                                        <option value="{{ $countryName }}" data-country-id="{{ $countryId }}" {{ $selectedCountry === $countryName ? 'selected' : '' }}>
                                                            {{ $countryName }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="account-helper">Selecting Kenya loads live county and sub-county dropdowns. Other countries switch to manual state and city inputs.</small>
                                                @error('country')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="col-md-6">
                                            <div class="account-field" data-location-mode="kenya-county" {{ $isKenyaSelected ? '' : 'hidden' }}>
                                                <label class="account-label" for="profile_county_select">County</label>
                                                <select id="profile_county_select" class="form-control account-select @error('county') is-invalid @enderror" data-location-county-select data-location-name="county" {{ $isKenyaSelected ? 'name=county required' : 'disabled' }}>
                                                    <option value="">Select a county</option>
                                                </select>
                                                <div class="account-county-note" data-county-note>
                                                    Pick one of Kenya's counties first. The sub-counties list loads immediately after.
                                                </div>
                                                <div class="account-location-status" data-county-status aria-live="polite"></div>
                                                @error('county')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="account-field" data-location-mode="global-county" {{ $isKenyaSelected ? 'hidden' : '' }}>
                                                <label class="account-label" for="profile_county_text">State / Province</label>
                                                <input type="text" id="profile_county_text" class="form-control account-input @error('county') is-invalid @enderror" value="{{ $isKenyaSelected ? '' : $selectedCounty }}" placeholder="State, province, or region" data-location-county-text data-location-name="county" {{ $isKenyaSelected ? 'disabled' : 'name=county' }}>
                                                <small class="account-helper">For countries outside Kenya, type the state, province, or region manually.</small>
                                                @error('county')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="account-field" data-location-mode="kenya-sub-county" {{ $isKenyaSelected ? '' : 'hidden' }}>
                                                <label class="account-label" for="profile_sub_county_select">Sub-county</label>
                                                <select id="profile_sub_county_select" class="form-control account-select @error('sub_county') is-invalid @enderror" data-location-sub-county-select data-location-name="sub_county" {{ $isKenyaSelected ? 'name=sub_county required' : 'disabled' }}>
                                                    <option value="">Select a sub-county</option>
                                                </select>
                                                <small class="account-helper">Choose the matching Kenyan sub-county after the county dropdown finishes loading.</small>
                                                <div class="account-location-status" data-sub-county-status aria-live="polite"></div>
                                                @error('sub_county')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="account-field" data-location-mode="global-sub-county" {{ $isKenyaSelected ? 'hidden' : '' }}>
                                                <label class="account-label" for="profile_sub_county_text">City / District / Area</label>
                                                <input type="text" id="profile_sub_county_text" class="form-control account-input @error('sub_county') is-invalid @enderror" value="{{ $isKenyaSelected ? '' : $selectedSubCounty }}" placeholder="City, district, county, or area" data-location-sub-county-text data-location-name="sub_county" {{ $isKenyaSelected ? 'disabled' : 'name=sub_county' }}>
                                                <small class="account-helper">For countries outside Kenya, type the city, district, or area manually.</small>
                                                @error('sub_county')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="col-md-6">
                                            <div class="account-field">
                                                <label class="account-label" for="address_line1">Address Line 1</label>
                                                <input type="text" id="address_line1" name="address_line1" class="form-control account-input @error('address_line1') is-invalid @enderror" value="{{ old('address_line1', $user->address_line1) }}" placeholder="Building, street, or delivery line">
                                                @error('address_line1')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="account-field">
                                                <label class="account-label" for="address_line2">Address Line 2</label>
                                                <input type="text" id="address_line2" name="address_line2" class="form-control account-input @error('address_line2') is-invalid @enderror" value="{{ old('address_line2', $user->address_line2) }}" placeholder="Apartment, floor, suite, or extra detail">
                                                @error('address_line2')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-row">
                                        <div class="col-md-6">
                                            <div class="account-field">
                                                <label class="account-label" for="estate">Estate / Neighborhood</label>
                                                <input type="text" id="estate" name="estate" class="form-control account-input @error('estate') is-invalid @enderror" value="{{ old('estate', $user->estate) }}" placeholder="Kilimani, Nyali, Karen, or neighborhood">
                                                @error('estate')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="account-field">
                                                <label class="account-label" for="landmark">Landmark</label>
                                                <textarea id="landmark" name="landmark" class="form-control account-textarea @error('landmark') is-invalid @enderror" placeholder="Opposite the mall, next to the stage, or any helpful landmark">{{ old('landmark', $user->landmark) }}</textarea>
                                                @error('landmark')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary px-5 account-submit-btn">Save Profile Details</button>
                                </form>

                                <div class="account-divider"></div>

                                <div>
                                    <span class="account-panel-kicker">Saved Addresses</span>
                                    <h4 class="account-section-title">Delivery Address Book</h4>
                                    <p class="account-section-copy">Save more than one delivery address and choose which one stays synced as your default profile address.</p>

                                    @if ($errors->addressBook->any())
                                        <div class="alert alert-danger">
                                            Please review the saved address form and try again.
                                        </div>
                                    @endif

                                    @if ($savedAddresses->isEmpty())
                                        <div class="address-book-empty">
                                            No saved addresses yet. Add one below to keep a reusable delivery address on your account.
                                        </div>
                                    @else
                                        <div class="address-book-grid">
                                            @foreach ($savedAddresses as $address)
                                                <div class="address-book-card {{ $address->is_default ? 'is-default' : '' }}">
                                                    <div class="address-book-head">
                                                        <div>
                                                            <div class="address-book-label">{{ $address->label }}</div>
                                                            @if ($address->recipient_name || $address->recipient_phone)
                                                                <div class="address-book-meta mb-2">
                                                                    @if ($address->recipient_name)
                                                                        Recipient: <strong>{{ $address->recipient_name }}</strong>
                                                                    @endif
                                                                    @if ($address->recipient_phone)
                                                                        @if ($address->recipient_name)
                                                                            |
                                                                        @endif
                                                                        Phone: <strong>{{ $address->recipient_phone }}</strong>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                            <div class="address-book-copy">
                                                                {{ $address->full_address ?: 'This saved address is still missing some location details.' }}
                                                            </div>
                                                        </div>

                                                        @if ($address->is_default)
                                                            <span class="account-status-badge" data-tone="success">Default</span>
                                                        @endif
                                                    </div>

                                                    <div class="address-book-meta">
                                                        Country: <strong>{{ $address->country ?: 'Kenya' }}</strong>
                                                        @if ($address->pincode)
                                                            | Pincode: <strong>{{ $address->pincode }}</strong>
                                                        @endif
                                                    </div>

                                                    <div class="address-book-actions">
                                                        <button
                                                            type="button"
                                                            class="btn btn-outline-secondary address-book-action"
                                                            data-address-edit
                                                            data-update-action="{{ route('user.account.addresses.update', ['address' => $address->id], false) }}"
                                                            data-address-id="{{ $address->id }}"
                                                             data-address-label="{{ $address->label }}"
                                                             data-address-full-name="{{ $address->full_name }}"
                                                             data-address-phone="{{ $address->phone }}"
                                                             data-address-country="{{ $address->country }}"
                                                             data-address-county="{{ $address->county }}"
                                                             data-address-sub-county="{{ $address->sub_county }}"
                                                             data-address-line1="{{ $address->address_line1 }}"
                                                             data-address-line2="{{ $address->address_line2 }}"
                                                             data-address-estate="{{ $address->estate }}"
                                                             data-address-landmark="{{ $address->landmark }}"
                                                             data-address-pincode="{{ $address->pincode }}"
                                                             data-address-default="{{ $address->is_default ? '1' : '0' }}"
                                                         >
                                                            Edit
                                                        </button>

                                                        @if (! $address->is_default)
                                                            <form method="POST" action="{{ route('user.account.addresses.default', ['address' => $address->id], false) }}" class="m-0">
                                                                @csrf
                                                                <button type="submit" class="btn btn-outline-primary address-book-action">Make Default</button>
                                                            </form>
                                                        @endif

                                                        <form method="POST" action="{{ route('user.account.addresses.destroy', ['address' => $address->id], false) }}" class="m-0" onsubmit="return confirm('Delete this saved address?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger address-book-action">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div class="address-book-form-shell">
                                        <span class="account-panel-kicker">Address Editor</span>
                                        <h5 class="account-section-title mb-2" data-address-book-title>{{ $isEditingAddress ? 'Edit Saved Address' : 'Add a Saved Address' }}</h5>
                                        <p class="account-section-copy mb-4">This form uses the same country, county, and sub-county logic as the main profile. If you mark an address as default, it also becomes the profile delivery address shown across the rest of the site.</p>

                                        <form
                                            method="POST"
                                            action="{{ $savedAddressFormAction }}"
                                            novalidate
                                            data-account-location-form
                                            data-address-book-form
                                            data-store-action="{{ route('user.account.addresses.store', [], false) }}"
                                            data-counties-url="{{ route('user.account.locations.counties', [], false) }}"
                                            data-sub-counties-url="{{ route('user.account.locations.sub-counties', [], false) }}"
                                            data-selected-county="{{ $savedAddressCounty }}"
                                            data-selected-sub-county="{{ $savedAddressSubCounty }}"
                                        >
                                            @csrf
                                            <input type="hidden" data-address-method @if ($isEditingAddress) name="_method" value="PUT" @endif>
                                            <input type="hidden" name="editing_address_id" value="{{ old('editing_address_id', $editingAddress?->id) }}" data-editing-address-id>

                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <div class="account-field">
                                                        <label class="account-label" for="saved_address_label">Label</label>
                                                        <input type="text" id="saved_address_label" name="saved_address_label" class="form-control account-input @error('saved_address_label', 'addressBook') is-invalid @enderror" value="{{ old('saved_address_label', $editingAddress?->label) }}" placeholder="Home, Office, Studio, or Pickup" required>
                                                        @error('saved_address_label', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="account-field">
                                                        <label class="account-label" for="saved_address_country">Country</label>
                                                        <select id="saved_address_country" name="saved_address_country" class="form-control account-select @error('saved_address_country', 'addressBook') is-invalid @enderror" data-location-country required>
                                                            <option value="">Select a country</option>
                                                            @foreach ($countries as $country)
                                                                @php
                                                                    $countryName = (string) data_get($country, 'name');
                                                                    $countryId = data_get($country, 'id');
                                                                @endphp
                                                                <option value="{{ $countryName }}" data-country-id="{{ $countryId }}" {{ $savedAddressCountry === $countryName ? 'selected' : '' }}>
                                                                    {{ $countryName }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <small class="account-helper">Kenya loads live counties and sub-counties. Other countries switch to manual state and city fields.</small>
                                                        @error('saved_address_country', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="col-md-4">
                                                    <div class="account-field">
                                                        <label class="account-label" for="saved_address_full_name">Recipient Name</label>
                                                        <input type="text" id="saved_address_full_name" name="saved_address_full_name" class="form-control account-input @error('saved_address_full_name', 'addressBook') is-invalid @enderror" value="{{ $savedAddressFullName }}" placeholder="Full name for this address">
                                                        @error('saved_address_full_name', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="account-field">
                                                        <label class="account-label" for="saved_address_phone">Phone Number</label>
                                                        <input type="text" id="saved_address_phone" name="saved_address_phone" class="form-control account-input @error('saved_address_phone', 'addressBook') is-invalid @enderror" value="{{ $savedAddressPhone }}" placeholder="+254 700 000 000">
                                                        @error('saved_address_phone', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="account-field">
                                                        <label class="account-label" for="saved_address_pincode">Pincode</label>
                                                        <input type="text" id="saved_address_pincode" name="saved_address_pincode" class="form-control account-input @error('saved_address_pincode', 'addressBook') is-invalid @enderror" value="{{ $savedAddressPincode }}" placeholder="00100">
                                                        @error('saved_address_pincode', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <div class="account-field" data-location-mode="kenya-county" {{ $savedAddressIsKenya ? '' : 'hidden' }}>
                                                        <label class="account-label" for="saved_address_county_select">County</label>
                                                        <select id="saved_address_county_select" class="form-control account-select @error('saved_address_county', 'addressBook') is-invalid @enderror" data-location-county-select data-location-name="saved_address_county" {{ $savedAddressIsKenya ? 'name=saved_address_county required' : 'disabled' }}>
                                                            <option value="">Select a county</option>
                                                        </select>
                                                        <div class="account-county-note">
                                                            Pick a Kenyan county first, then the sub-county list will load automatically.
                                                        </div>
                                                        <div class="account-location-status" data-county-status aria-live="polite"></div>
                                                        @error('saved_address_county', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    <div class="account-field" data-location-mode="global-county" {{ $savedAddressIsKenya ? 'hidden' : '' }}>
                                                        <label class="account-label" for="saved_address_county_text">State / Province</label>
                                                        <input type="text" id="saved_address_county_text" class="form-control account-input @error('saved_address_county', 'addressBook') is-invalid @enderror" value="{{ $savedAddressIsKenya ? '' : $savedAddressCounty }}" placeholder="State, province, or region" data-location-county-text data-location-name="saved_address_county" data-manual-required="true" {{ $savedAddressIsKenya ? 'disabled' : 'name=saved_address_county' }}>
                                                        <small class="account-helper">For non-Kenyan addresses, type the state, province, county, or region manually.</small>
                                                        @error('saved_address_county', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="account-field" data-location-mode="kenya-sub-county" {{ $savedAddressIsKenya ? '' : 'hidden' }}>
                                                        <label class="account-label" for="saved_address_sub_county_select">Sub-county</label>
                                                        <select id="saved_address_sub_county_select" class="form-control account-select @error('saved_address_sub_county', 'addressBook') is-invalid @enderror" data-location-sub-county-select data-location-name="saved_address_sub_county" {{ $savedAddressIsKenya ? 'name=saved_address_sub_county required' : 'disabled' }}>
                                                            <option value="">Select a sub-county</option>
                                                        </select>
                                                        <small class="account-helper">Choose the Kenyan sub-county after the county list has loaded.</small>
                                                        <div class="account-location-status" data-sub-county-status aria-live="polite"></div>
                                                        @error('saved_address_sub_county', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>

                                                    <div class="account-field" data-location-mode="global-sub-county" {{ $savedAddressIsKenya ? 'hidden' : '' }}>
                                                        <label class="account-label" for="saved_address_sub_county_text">City / District / Area</label>
                                                        <input type="text" id="saved_address_sub_county_text" class="form-control account-input @error('saved_address_sub_county', 'addressBook') is-invalid @enderror" value="{{ $savedAddressIsKenya ? '' : $savedAddressSubCounty }}" placeholder="City, district, county, or area" data-location-sub-county-text data-location-name="saved_address_sub_county" data-manual-required="true" {{ $savedAddressIsKenya ? 'disabled' : 'name=saved_address_sub_county' }}>
                                                        <small class="account-helper">For non-Kenyan addresses, type the city, district, or area manually.</small>
                                                        @error('saved_address_sub_county', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <div class="account-field">
                                                        <label class="account-label" for="saved_address_line1">Address Line 1</label>
                                                        <input type="text" id="saved_address_line1" name="saved_address_line1" class="form-control account-input @error('saved_address_line1', 'addressBook') is-invalid @enderror" value="{{ old('saved_address_line1', $editingAddress?->address_line1) }}" placeholder="Building, street, or delivery line" required>
                                                        @error('saved_address_line1', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="account-field">
                                                        <label class="account-label" for="saved_address_line2">Address Line 2</label>
                                                        <input type="text" id="saved_address_line2" name="saved_address_line2" class="form-control account-input @error('saved_address_line2', 'addressBook') is-invalid @enderror" value="{{ old('saved_address_line2', $editingAddress?->address_line2) }}" placeholder="Apartment, floor, suite, or extra detail">
                                                        @error('saved_address_line2', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-row">
                                                <div class="col-md-6">
                                                    <div class="account-field">
                                                        <label class="account-label" for="saved_address_estate">Estate / Neighborhood</label>
                                                        <input type="text" id="saved_address_estate" name="saved_address_estate" class="form-control account-input @error('saved_address_estate', 'addressBook') is-invalid @enderror" value="{{ old('saved_address_estate', $editingAddress?->estate) }}" placeholder="Kilimani, Nyali, Karen, or neighborhood">
                                                        @error('saved_address_estate', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <div class="account-field">
                                                        <label class="account-label" for="saved_address_landmark">Landmark</label>
                                                        <textarea id="saved_address_landmark" name="saved_address_landmark" class="form-control account-textarea @error('saved_address_landmark', 'addressBook') is-invalid @enderror" placeholder="Opposite the mall, next to the stage, or any helpful landmark">{{ old('saved_address_landmark', $editingAddress?->landmark) }}</textarea>
                                                        @error('saved_address_landmark', 'addressBook')
                                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="account-field">
                                                <label class="account-checkbox">
                                                    <input type="checkbox" name="saved_address_make_default" value="1" {{ $savedAddressShouldBeDefault ? 'checked' : '' }} data-address-default-checkbox>
                                                    <span>Set this as the default delivery address for the rest of the account.</span>
                                                </label>
                                            </div>

                                            <div class="account-button-row">
                                                <button type="submit" class="btn btn-outline-primary px-5 account-submit-btn" data-address-book-submit>{{ $isEditingAddress ? 'Update Saved Address' : 'Save Saved Address' }}</button>
                                                <button type="button" class="btn btn-outline-secondary px-5 account-submit-btn {{ $isEditingAddress ? '' : 'd-none' }}" data-address-book-cancel>Edit Another Later</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <div class="account-divider"></div>

                                <div>
                                    <span class="account-panel-kicker">Wallet Activity</span>
                                    <h4 class="account-section-title">Recent Wallet Entries</h4>
                                    <p class="account-section-copy">Every credit and debit applied to your account is listed here so you can verify promotions, wallet-only checkout debits, and any remaining spendable balance.</p>

                                    <div class="account-status-strip mb-4">
                                        <span class="account-status-badge" data-tone="{{ $walletBalance > 0 ? 'success' : 'warning' }}">
                                            {{ $walletBalance > 0 ? 'Balance Available' : 'No Active Balance' }}
                                        </span>
                                        <span class="small text-muted">
                                            Current live wallet balance: <strong>KSH.{{ number_format($walletBalance, 2) }}</strong>
                                        </span>
                                    </div>

                                    @if ($walletEntries->isEmpty())
                                        <div class="wallet-history-empty">
                                            No wallet transactions yet. Once credits or debits are posted to your account, they will appear here.
                                        </div>
                                    @else
                                        <div class="wallet-history-list">
                                            @foreach ($walletEntries as $entry)
                                                <div class="wallet-history-item">
                                                    <div>
                                                        <div class="wallet-history-status">
                                                            <span class="account-status-badge" data-tone="{{ $entry->action === 'credit' ? 'success' : 'warning' }}">
                                                                {{ ucfirst($entry->action) }}
                                                            </span>
                                                            <span class="account-status-badge" data-tone="{{ $entry->status ? 'success' : 'warning' }}">
                                                                {{ $entry->status ? 'Active' : 'Inactive' }}
                                                            </span>
                                                            @if ($entry->is_expired)
                                                                <span class="account-status-badge" data-tone="warning">Expired</span>
                                                            @endif
                                                        </div>
                                                        <div class="wallet-history-description">
                                                            {{ $entry->description ?: 'Wallet adjustment recorded.' }}
                                                        </div>
                                                        <div class="wallet-history-meta">
                                                            Posted {{ optional($entry->created_at)->format('F j, Y, g:i a') }}
                                                            @if ($entry->expiry_date)
                                                                | Expires {{ $entry->expiry_date->format('F j, Y') }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="wallet-history-amount {{ $entry->signed_amount >= 0 ? 'is-credit' : 'is-debit' }}">
                                                        {{ $entry->signed_amount >= 0 ? '+' : '-' }}KSH.{{ number_format(abs((float) $entry->signed_amount), 2) }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="account-divider"></div>

                                <div>
                                    <span class="account-panel-kicker">Security</span>
                                    <h4 class="account-section-title">Change Password</h4>
                                    <p class="account-section-copy">Use your current password to confirm the update, then choose a stronger one for future logins.</p>

                                    @if ($errors->passwordUpdate->any())
                                        <div class="alert alert-danger">
                                            Please review the password form and try again.
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('user.account.password.update', [], false) }}" novalidate>
                                        @csrf
                                        @method('PUT')

                                        <div class="form-row">
                                            <div class="col-md-4">
                                                <div class="account-field">
                                                    <label class="account-label" for="current_password">Current Password</label>
                                                    <input type="password" id="current_password" name="current_password" class="form-control account-input @error('current_password', 'passwordUpdate') is-invalid @enderror" autocomplete="current-password" required>
                                                    @error('current_password', 'passwordUpdate')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="account-field">
                                                    <label class="account-label" for="new_password">New Password</label>
                                                    <input type="password" id="new_password" name="new_password" class="form-control account-input @error('new_password', 'passwordUpdate') is-invalid @enderror" autocomplete="new-password" required>
                                                    @error('new_password', 'passwordUpdate')
                                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-md-4">
                                                <div class="account-field">
                                                    <label class="account-label" for="new_password_confirmation">Confirm New Password</label>
                                                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" class="form-control account-input" autocomplete="new-password" required>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-outline-primary px-5 account-submit-btn">Update Password</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const locationForms = Array.from(document.querySelectorAll('[data-account-location-form]'));

        locationForms.forEach((form) => {
            const countrySelect = form.querySelector('[data-location-country]');
            const countySelect = form.querySelector('[data-location-county-select]');
            const subCountySelect = form.querySelector('[data-location-sub-county-select]');
            const countyText = form.querySelector('[data-location-county-text]');
            const subCountyText = form.querySelector('[data-location-sub-county-text]');
            const kenyaCountyWrapper = form.querySelector('[data-location-mode="kenya-county"]');
            const kenyaSubCountyWrapper = form.querySelector('[data-location-mode="kenya-sub-county"]');
            const globalCountyWrapper = form.querySelector('[data-location-mode="global-county"]');
            const globalSubCountyWrapper = form.querySelector('[data-location-mode="global-sub-county"]');
            const countyStatus = form.querySelector('[data-county-status]');
            const subCountyStatus = form.querySelector('[data-sub-county-status]');

            if (
                !countrySelect ||
                !countySelect ||
                !subCountySelect ||
                !countyText ||
                !subCountyText ||
                !kenyaCountyWrapper ||
                !kenyaSubCountyWrapper ||
                !globalCountyWrapper ||
                !globalSubCountyWrapper ||
                !countyStatus ||
                !subCountyStatus
            ) {
                return;
            }

            const countiesUrl = form.dataset.countiesUrl;
            const subCountiesUrl = form.dataset.subCountiesUrl;
            const countyFieldName = countySelect.dataset.locationName || countyText.dataset.locationName || 'county';
            const subCountyFieldName = subCountySelect.dataset.locationName || subCountyText.dataset.locationName || 'sub_county';
            const countyManualRequired = countyText.dataset.manualRequired === 'true';
            const subCountyManualRequired = subCountyText.dataset.manualRequired === 'true';
            let pendingCounty = form.dataset.selectedCounty || countyText.value || '';
            let pendingSubCounty = form.dataset.selectedSubCounty || subCountyText.value || '';

            const resetSelect = (select, placeholder) => {
                select.innerHTML = '';

                const option = document.createElement('option');
                option.value = '';
                option.textContent = placeholder;
                select.appendChild(option);
            };

            const setStatus = (element, state, message) => {
                element.dataset.state = state || '';
                element.textContent = message || '';
                element.classList.toggle('is-visible', Boolean(message));
            };

            const getSelectedCountryOption = () => countrySelect.options[countrySelect.selectedIndex] || null;
            const getSelectedCountyOption = () => countySelect.options[countySelect.selectedIndex] || null;
            const isKenyaSelected = () => countrySelect.value.trim().toLowerCase() === 'kenya';
            const selectedCountryId = () => getSelectedCountryOption()?.dataset.countryId || '';
            const selectedCountyId = () => getSelectedCountyOption()?.dataset.countyId || '';

            const setKenyaMode = (enabled) => {
                kenyaCountyWrapper.hidden = !enabled;
                kenyaSubCountyWrapper.hidden = !enabled;
                globalCountyWrapper.hidden = enabled;
                globalSubCountyWrapper.hidden = enabled;

                countySelect.disabled = !enabled;
                subCountySelect.disabled = !enabled;
                countySelect.required = enabled;
                subCountySelect.required = enabled;

                countyText.disabled = enabled;
                subCountyText.disabled = enabled;
                countyText.required = !enabled && countyManualRequired;
                subCountyText.required = !enabled && subCountyManualRequired;

                if (enabled) {
                    countySelect.setAttribute('name', countyFieldName);
                    subCountySelect.setAttribute('name', subCountyFieldName);
                    countyText.removeAttribute('name');
                    subCountyText.removeAttribute('name');
                    return;
                }

                countyText.setAttribute('name', countyFieldName);
                subCountyText.setAttribute('name', subCountyFieldName);
                countySelect.removeAttribute('name');
                subCountySelect.removeAttribute('name');
            };

            const populateCounties = async (countryId, selectedCounty) => {
                if (!countryId) {
                    resetSelect(countySelect, 'Select a county');
                    resetSelect(subCountySelect, 'Select a sub-county');
                    setStatus(countyStatus, '', '');
                    setStatus(subCountyStatus, '', '');
                    return;
                }

                countySelect.disabled = true;
                subCountySelect.disabled = true;
                resetSelect(countySelect, 'Loading counties...');
                resetSelect(subCountySelect, 'Select a sub-county');
                setStatus(countyStatus, 'loading', 'Loading counties for the selected country...');
                setStatus(subCountyStatus, '', '');

                try {
                    const response = await fetch(`${countiesUrl}?country_id=${encodeURIComponent(countryId)}`, {
                        headers: {
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('Unable to load counties.');
                    }

                    const payload = await response.json();
                    const counties = Array.isArray(payload.counties) ? payload.counties : [];

                    resetSelect(countySelect, counties.length ? 'Select a county' : 'No counties available');

                    counties.forEach((county) => {
                        const option = document.createElement('option');
                        option.value = county.name;
                        option.textContent = county.name;
                        option.dataset.countyId = county.id;

                        if (selectedCounty && selectedCounty === county.name) {
                            option.selected = true;
                        }

                        countySelect.appendChild(option);
                    });

                    countySelect.disabled = false;
                    subCountySelect.disabled = false;
                    setStatus(
                        countyStatus,
                        counties.length ? 'success' : 'error',
                        counties.length ? `${counties.length} counties loaded.` : 'No counties were found for the selected country.'
                    );
                } catch (error) {
                    resetSelect(countySelect, 'Unable to load counties');
                    resetSelect(subCountySelect, 'Unable to load sub-counties');
                    countySelect.disabled = false;
                    subCountySelect.disabled = false;
                    setStatus(countyStatus, 'error', 'Unable to load counties right now. Try changing the country again.');
                    setStatus(subCountyStatus, '', '');
                }
            };

            const populateSubCounties = async (countyId, selectedSubCounty) => {
                if (!countyId) {
                    resetSelect(subCountySelect, 'Select a sub-county');
                    setStatus(subCountyStatus, '', '');
                    return;
                }

                subCountySelect.disabled = true;
                resetSelect(subCountySelect, 'Loading sub-counties...');
                setStatus(subCountyStatus, 'loading', 'Loading sub-counties for the selected county...');

                try {
                    const response = await fetch(`${subCountiesUrl}?county_id=${encodeURIComponent(countyId)}`, {
                        headers: {
                            Accept: 'application/json',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('Unable to load sub-counties.');
                    }

                    const payload = await response.json();
                    const subCounties = Array.isArray(payload.sub_counties) ? payload.sub_counties : [];

                    resetSelect(subCountySelect, subCounties.length ? 'Select a sub-county' : 'No sub-counties available');

                    subCounties.forEach((subCounty) => {
                        const option = document.createElement('option');
                        option.value = subCounty.name;
                        option.textContent = subCounty.name;

                        if (selectedSubCounty && selectedSubCounty === subCounty.name) {
                            option.selected = true;
                        }

                        subCountySelect.appendChild(option);
                    });

                    subCountySelect.disabled = false;
                    setStatus(
                        subCountyStatus,
                        subCounties.length ? 'success' : 'error',
                        subCounties.length ? `${subCounties.length} sub-counties loaded.` : 'No sub-counties were found for the selected county.'
                    );
                } catch (error) {
                    resetSelect(subCountySelect, 'Unable to load sub-counties');
                    subCountySelect.disabled = false;
                    setStatus(subCountyStatus, 'error', 'Unable to load sub-counties right now. Try selecting the county again.');
                }
            };

            const syncLocationMode = async () => {
                const kenyaSelected = isKenyaSelected();
                setKenyaMode(kenyaSelected);

                if (!kenyaSelected) {
                    countyText.value = pendingCounty;
                    subCountyText.value = pendingSubCounty;
                    resetSelect(countySelect, 'Select a county');
                    resetSelect(subCountySelect, 'Select a sub-county');
                    setStatus(countyStatus, '', '');
                    setStatus(subCountyStatus, '', '');
                    pendingCounty = '';
                    pendingSubCounty = '';
                    return;
                }

                await populateCounties(selectedCountryId(), pendingCounty);
                await populateSubCounties(selectedCountyId(), pendingSubCounty);

                pendingCounty = '';
                pendingSubCounty = '';
            };

            const applySelections = async ({ country = countrySelect.value || '', county = '', subCounty = '' } = {}) => {
                if (country) {
                    countrySelect.value = country;
                }

                pendingCounty = county || '';
                pendingSubCounty = subCounty || '';
                countyText.value = pendingCounty;
                subCountyText.value = pendingSubCounty;

                await syncLocationMode();
            };

            countrySelect.addEventListener('change', async function () {
                if (isKenyaSelected()) {
                    countyText.value = '';
                    subCountyText.value = '';
                    pendingCounty = '';
                    pendingSubCounty = '';
                } else {
                    pendingCounty = countyText.value || '';
                    pendingSubCounty = subCountyText.value || '';
                }

                await syncLocationMode();
            });

            countySelect.addEventListener('change', async function () {
                pendingSubCounty = '';
                await populateSubCounties(selectedCountyId(), '');
            });

            form.__locationApi = {
                applySelections,
            };

            syncLocationMode();
        });

        const addressBookForm = document.querySelector('[data-address-book-form]');

        if (!addressBookForm) {
            return;
        }

        const addressTitle = document.querySelector('[data-address-book-title]');
        const addressSubmit = addressBookForm.querySelector('[data-address-book-submit]');
        const addressCancel = addressBookForm.querySelector('[data-address-book-cancel]');
        const addressMethod = addressBookForm.querySelector('[data-address-method]');
        const editingAddressId = addressBookForm.querySelector('[data-editing-address-id]');
        const addressLabel = document.getElementById('saved_address_label');
        const addressFullName = document.getElementById('saved_address_full_name');
        const addressPhone = document.getElementById('saved_address_phone');
        const addressPincode = document.getElementById('saved_address_pincode');
        const addressLine1 = document.getElementById('saved_address_line1');
        const addressLine2 = document.getElementById('saved_address_line2');
        const addressEstate = document.getElementById('saved_address_estate');
        const addressLandmark = document.getElementById('saved_address_landmark');
        const addressDefaultCheckbox = addressBookForm.querySelector('[data-address-default-checkbox]');
        const createModeDefaults = {
            country: 'Kenya',
            defaultChecked: {{ $savedAddresses->isEmpty() ? 'true' : 'false' }},
        };

        const setCreateMode = async () => {
            addressTitle.textContent = 'Add a Saved Address';
            addressSubmit.textContent = 'Save Saved Address';
            addressCancel.classList.add('d-none');
            addressBookForm.action = addressBookForm.dataset.storeAction;
            addressMethod.removeAttribute('name');
            addressMethod.value = '';
            editingAddressId.value = '';
            addressLabel.value = '';
            addressFullName.value = '{{ addslashes((string) ($user->name ?? '')) }}';
            addressPhone.value = '{{ addslashes((string) ($user->phone ?? '')) }}';
            addressPincode.value = '';
            addressLine1.value = '';
            addressLine2.value = '';
            addressEstate.value = '';
            addressLandmark.value = '';
            addressDefaultCheckbox.checked = createModeDefaults.defaultChecked;

            if (addressBookForm.__locationApi) {
                await addressBookForm.__locationApi.applySelections({
                    country: createModeDefaults.country,
                    county: '',
                    subCounty: '',
                });
            }
        };

        const setEditMode = async (button) => {
            addressTitle.textContent = 'Edit Saved Address';
            addressSubmit.textContent = 'Update Saved Address';
            addressCancel.classList.remove('d-none');
            addressBookForm.action = button.dataset.updateAction;
            addressMethod.setAttribute('name', '_method');
            addressMethod.value = 'PUT';
            editingAddressId.value = button.dataset.addressId || '';
            addressLabel.value = button.dataset.addressLabel || '';
            addressFullName.value = button.dataset.addressFullName || '';
            addressPhone.value = button.dataset.addressPhone || '';
            addressPincode.value = button.dataset.addressPincode || '';
            addressLine1.value = button.dataset.addressLine1 || '';
            addressLine2.value = button.dataset.addressLine2 || '';
            addressEstate.value = button.dataset.addressEstate || '';
            addressLandmark.value = button.dataset.addressLandmark || '';
            addressDefaultCheckbox.checked = button.dataset.addressDefault === '1';

            if (addressBookForm.__locationApi) {
                await addressBookForm.__locationApi.applySelections({
                    country: button.dataset.addressCountry || 'Kenya',
                    county: button.dataset.addressCounty || '',
                    subCounty: button.dataset.addressSubCounty || '',
                });
            }

            addressBookForm.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        };

        document.querySelectorAll('[data-address-edit]').forEach((button) => {
            button.addEventListener('click', function () {
                setEditMode(button);
            });
        });

        addressCancel.addEventListener('click', function () {
            setCreateMode();
        });
    });
</script>
@endpush
