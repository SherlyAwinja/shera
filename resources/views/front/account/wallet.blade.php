@extends('front.layout.layout')

@php
    $walletBalance = (float) ($walletBalance ?? 0);
    $walletEntries = collect($walletEntries ?? [])->values();
    $pendingWalletRequests = $walletEntries->filter(fn ($entry) => $entry->is_pending_top_up_request)->values();
    $creditEntriesCount = $walletEntries->where('action', 'credit')->count();
    $debitEntriesCount = $walletEntries->where('action', 'debit')->count();
    $displayName = $user->name ?: $user->email;
@endphp

@push('styles')
<style>
    .wallet-page {
        padding: 2.25rem 0 4rem;
    }

    .wallet-shell {
        background:
            radial-gradient(circle at top right, rgba(196, 164, 92, 0.18), transparent 38%),
            linear-gradient(180deg, #f8f3e7 0%, #ffffff 100%);
        border: 1px solid rgba(15, 95, 115, 0.12);
        border-radius: 28px;
        box-shadow: 0 24px 54px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .wallet-aside {
        background:
            radial-gradient(circle at top, rgba(196, 164, 92, 0.2), transparent 46%),
            linear-gradient(180deg, #0f5f73 0%, #12323b 100%);
        color: #f7f3e8;
        height: 100%;
        padding: 2.5rem 2.2rem;
    }

    .wallet-kicker {
        color: #c4a45c;
        display: inline-block;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.18em;
        margin-bottom: 1rem;
        text-transform: uppercase;
    }

    .wallet-title {
        font-family: 'Cormorant Garamond', serif;
        font-size: clamp(2.1rem, 3vw, 3rem);
        font-weight: 700;
        line-height: 1.04;
        margin-bottom: 0.85rem;
    }

    .wallet-copy {
        color: rgba(247, 243, 232, 0.82);
        line-height: 1.75;
        margin-bottom: 1.5rem;
    }

    .wallet-stat-grid {
        display: grid;
        gap: 0.9rem;
        margin-bottom: 1.5rem;
    }

    .wallet-stat-card {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 18px;
        padding: 1rem 1.1rem;
    }

    .wallet-stat-label {
        color: rgba(247, 243, 232, 0.72);
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        margin-bottom: 0.35rem;
        text-transform: uppercase;
    }

    .wallet-stat-value {
        font-size: 1.45rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .wallet-nav-links {
        display: grid;
        gap: 0.75rem;
    }

    .wallet-nav-link {
        align-items: center;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 14px;
        color: #f7f3e8;
        display: flex;
        font-weight: 600;
        justify-content: space-between;
        padding: 0.9rem 1rem;
        text-decoration: none;
        transition: transform 0.2s ease, background-color 0.2s ease;
    }

    .wallet-nav-link:hover,
    .wallet-nav-link:focus {
        background: rgba(255, 255, 255, 0.16);
        color: #fff;
        text-decoration: none;
        transform: translateY(-1px);
    }

    .wallet-main {
        padding: 2.5rem 2.2rem;
    }

    .wallet-section + .wallet-section {
        border-top: 1px solid rgba(15, 23, 42, 0.08);
        margin-top: 2rem;
        padding-top: 2rem;
    }

    .wallet-section-title {
        color: #142735;
        font-family: 'Cormorant Garamond', serif;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 0.6rem;
    }

    .wallet-section-copy {
        color: #52606d;
        line-height: 1.8;
        margin-bottom: 1.25rem;
    }

    .wallet-status-row {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
        margin-bottom: 1.25rem;
    }

    .wallet-badge {
        border-radius: 999px;
        display: inline-flex;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        padding: 0.45rem 0.8rem;
        text-transform: uppercase;
    }

    .wallet-badge[data-tone="success"] {
        background: rgba(20, 132, 92, 0.12);
        color: #0d7a52;
    }

    .wallet-badge[data-tone="warning"] {
        background: rgba(191, 90, 36, 0.12);
        color: #a24c1f;
    }

    .wallet-badge[data-tone="neutral"] {
        background: rgba(15, 23, 42, 0.08);
        color: #334155;
    }

    .wallet-form-card {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 22px;
        padding: 1.4rem;
    }

    .wallet-field + .wallet-field {
        margin-top: 1rem;
    }

    .wallet-label {
        color: #142735;
        display: block;
        font-size: 0.88rem;
        font-weight: 700;
        margin-bottom: 0.45rem;
    }

    .wallet-helper {
        color: #6b7280;
        display: block;
        font-size: 0.82rem;
        margin-top: 0.4rem;
    }

    .wallet-input,
    .wallet-textarea {
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: 14px;
        min-height: 54px;
        padding: 0.8rem 1rem;
    }

    .wallet-textarea {
        min-height: 130px;
        resize: vertical;
    }

    .wallet-submit {
        min-height: 54px;
        padding-inline: 1.6rem;
    }

    .wallet-history-list {
        display: grid;
        gap: 1rem;
    }

    .wallet-history-item {
        align-items: flex-start;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 22px;
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        padding: 1.15rem 1.2rem;
    }

    .wallet-history-header {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 0.7rem;
    }

    .wallet-history-description {
        color: #142735;
        font-weight: 600;
        margin-bottom: 0.4rem;
    }

    .wallet-history-meta {
        color: #64748b;
        font-size: 0.92rem;
    }

    .wallet-history-amount {
        font-size: 1.12rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .wallet-history-amount.is-credit {
        color: #0d7a52;
    }

    .wallet-history-amount.is-debit {
        color: #b45309;
    }

    .wallet-empty {
        background: rgba(255, 255, 255, 0.88);
        border: 1px dashed rgba(15, 23, 42, 0.16);
        border-radius: 22px;
        color: #52606d;
        padding: 1.3rem 1.4rem;
    }

    @media (max-width: 991.98px) {
        .wallet-main,
        .wallet-aside {
            padding: 2rem 1.4rem;
        }
    }

    @media (max-width: 575.98px) {
        .wallet-history-item {
            flex-direction: column;
        }

        .wallet-history-amount {
            white-space: normal;
        }
    }
</style>
@endpush

@section('content')
<section class="wallet-page">
    <div class="container-fluid pt-4">
        <div class="wallet-shell">
            <div class="row no-gutters">
                <div class="col-lg-4">
                    <aside class="wallet-aside">
                        <span class="wallet-kicker">My Wallet</span>
                        <h1 class="wallet-title">Wallet balance, requests, and activity in one place.</h1>
                        <p class="wallet-copy">
                            Hi, {{ $displayName }}. This page shows your spendable wallet balance, pending top-up requests, and the latest credits or debits applied to your account.
                        </p>

                        <div class="wallet-stat-grid">
                            <div class="wallet-stat-card">
                                <span class="wallet-stat-label">Live Balance</span>
                                <div class="wallet-stat-value">KSH.{{ number_format($walletBalance, 2) }}</div>
                            </div>
                            <div class="wallet-stat-card">
                                <span class="wallet-stat-label">Pending Requests</span>
                                <div class="wallet-stat-value">{{ $pendingWalletRequests->count() }}</div>
                            </div>
                            <div class="wallet-stat-card">
                                <span class="wallet-stat-label">Recent Entries</span>
                                <div class="wallet-stat-value">{{ $walletEntries->count() }}</div>
                                <div class="small mt-2 text-white-50">{{ $creditEntriesCount }} credit(s), {{ $debitEntriesCount }} debit(s)</div>
                            </div>
                        </div>

                        <div class="wallet-nav-links">
                            <a href="{{ route('user.account', [], false) }}" class="wallet-nav-link">
                                <span>My Account</span>
                                <i class="fas fa-user"></i>
                            </a>
                            <a href="{{ route('user.orders.index', [], false) }}" class="wallet-nav-link">
                                <span>My Orders</span>
                                <i class="fas fa-box"></i>
                            </a>
                        </div>
                    </aside>
                </div>

                <div class="col-lg-8">
                    <div class="wallet-main">
                        <div class="wallet-section">
                            <span class="wallet-kicker">Top-Up Request</span>
                            <h2 class="wallet-section-title">Request money to be added to your wallet</h2>
                            <p class="wallet-section-copy">
                                Top-ups requested here do not become spendable immediately. They remain pending until an admin reviews and activates the wallet credit.
                            </p>

                            @if (session('wallet_top_up_success'))
                                <div class="alert alert-success">{{ session('wallet_top_up_success') }}</div>
                            @endif

                            <div class="wallet-status-row">
                                <span class="wallet-badge" data-tone="{{ $walletBalance > 0 ? 'success' : 'warning' }}">
                                    {{ $walletBalance > 0 ? 'Balance Available' : 'No Active Balance' }}
                                </span>
                                <span class="wallet-badge" data-tone="{{ $pendingWalletRequests->isNotEmpty() ? 'warning' : 'success' }}">
                                    {{ $pendingWalletRequests->isNotEmpty() ? $pendingWalletRequests->count() . ' Pending Request(s)' : 'No Pending Request' }}
                                </span>
                            </div>

                            @if ($errors->walletTopUp->any())
                                <div class="alert alert-danger">
                                    Please review the wallet top-up form and try again.
                                </div>
                            @endif

                            <div class="wallet-form-card">
                                <form method="POST" action="{{ route('user.account.wallet.top-up', [], false) }}" novalidate>
                                    @csrf

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="wallet-field">
                                                <label class="wallet-label" for="wallet_amount">Top-Up Amount</label>
                                                <input type="number" min="1" step="0.01" id="wallet_amount" name="amount" class="form-control wallet-input @error('amount', 'walletTopUp') is-invalid @enderror" value="{{ old('amount') }}" placeholder="1000.00" required>
                                                <small class="wallet-helper">Enter the amount you want added after admin approval.</small>
                                                @error('amount', 'walletTopUp')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="wallet-field">
                                                <label class="wallet-label" for="wallet_note">Reference / Note</label>
                                                <input type="text" id="wallet_note" name="note" class="form-control wallet-input @error('note', 'walletTopUp') is-invalid @enderror" value="{{ old('note') }}" placeholder="Mpesa reference, transfer note, or reason">
                                                <small class="wallet-helper">Optional context for the admin reviewing your request.</small>
                                                @error('note', 'walletTopUp')
                                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-outline-primary wallet-submit w-100">Request Top Up</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="wallet-section">
                            <span class="wallet-kicker">Activity</span>
                            <h2 class="wallet-section-title">Recent wallet entries</h2>
                            <p class="wallet-section-copy">
                                Every wallet credit, debit, pending top-up request, and expiry notice recorded for your account appears here.
                            </p>

                            @if ($walletEntries->isEmpty())
                                <div class="wallet-empty">
                                    No wallet transactions yet. Once credits or debits are posted to your account, they will appear here.
                                </div>
                            @else
                                <div class="wallet-history-list">
                                    @foreach ($walletEntries as $entry)
                                        <div class="wallet-history-item">
                                            <div>
                                                <div class="wallet-history-header">
                                                    <span class="wallet-badge" data-tone="{{ $entry->action === 'credit' ? 'success' : 'warning' }}">
                                                        {{ ucfirst($entry->action) }}
                                                    </span>
                                                    <span class="wallet-badge" data-tone="{{ $entry->status ? 'success' : 'neutral' }}">
                                                        {{ $entry->status ? 'Active' : 'Inactive' }}
                                                    </span>
                                                    @if ($entry->is_pending_top_up_request)
                                                        <span class="wallet-badge" data-tone="warning">Pending Top-Up Request</span>
                                                    @endif
                                                    @if ($entry->is_expired)
                                                        <span class="wallet-badge" data-tone="warning">Expired</span>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
