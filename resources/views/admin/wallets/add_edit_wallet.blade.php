@extends('admin.layout.layout')

@section('content')
<main class="app-main">
    @php
        $selectedUserId = old('user_id', $wallet->user_id ?? null);
        $cancelUserId = old('user_id', $returnUserId ?? $wallet->user_id ?? null);
    @endphp

    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">{{ $title }}</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('wallets.index') }}">Wallets</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card mb-4">
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ $wallet->exists ? route('wallets.update', $wallet->id) : route('wallets.store') }}" method="POST">
                        @csrf
                        @if($wallet->exists)
                            @method('PUT')
                        @endif

                        <input type="hidden" id="walletId" value="{{ $wallet->id ?? '' }}">

                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label for="walletUserSelect" class="form-label">User</label>
                                        <select name="user_id" id="walletUserSelect" class="form-select" required>
                                            <option value="">Select user</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" {{ (string) $selectedUserId === (string) $user->id ? 'selected' : '' }}>
                                                    {{ $user->name ?: 'User #' . $user->id }} ({{ $user->email }}) - Balance: KES {{ number_format((float) ($user->wallet_balance ?? 0), 2) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label d-block">Action</label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input"
                                                   type="radio"
                                                   name="action"
                                                   id="walletActionCredit"
                                                   value="credit"
                                                   {{ old('action', $wallet->action ?? 'credit') === 'credit' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="walletActionCredit">Credit</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input"
                                                   type="radio"
                                                   name="action"
                                                   id="walletActionDebit"
                                                   value="debit"
                                                   {{ old('action', $wallet->action ?? 'credit') === 'debit' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="walletActionDebit">Debit</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="walletAmount" class="form-label">Amount</label>
                                        <input type="number"
                                               min="0.01"
                                               step="0.01"
                                               name="amount"
                                               id="walletAmount"
                                               class="form-control"
                                               value="{{ old('amount', isset($wallet->amount) ? number_format((float) $wallet->amount, 2, '.', '') : '') }}"
                                               required>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="walletExpiryDate" class="form-label">Expiry Date</label>
                                        <input type="date"
                                               name="expiry_date"
                                               id="walletExpiryDate"
                                               class="form-control"
                                               value="{{ old('expiry_date', optional($wallet->expiry_date)->format('Y-m-d')) }}">
                                        <small class="text-muted">Leave blank to keep the entry available until manually deactivated.</small>
                                    </div>

                                    <div class="col-md-6 d-flex align-items-center">
                                        <div class="form-check mt-4">
                                            <input type="checkbox"
                                                   class="form-check-input"
                                                   name="status"
                                                   id="walletStatus"
                                                   value="1"
                                                   {{ old('status', (int) ($wallet->status ?? 1)) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="walletStatus">Active</label>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <label for="walletDescription" class="form-label">Details</label>
                                        <textarea name="description"
                                                  id="walletDescription"
                                                  rows="4"
                                                  class="form-control"
                                                  placeholder="Add a short reason or note for this wallet movement">{{ old('description', $wallet->description ?? '') }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div id="walletBalancePanel"
                                     class="border rounded p-3 bg-light"
                                     data-base-balance="0">
                                    <div class="small text-uppercase text-muted fw-semibold mb-2" id="walletBalanceLabel">
                                        Current live balance
                                    </div>
                                    <div class="display-6 fw-bold mb-3" id="walletCurrentBalance">KES 0.00</div>

                                    <div class="small text-uppercase text-muted fw-semibold mb-2">Projected balance after save</div>
                                    <div class="h3 fw-bold mb-2" id="walletProjectedBalance">KES 0.00</div>

                                    <p class="text-muted small mb-0" id="walletBalanceHelp">
                                        Select a user to load live balance.
                                    </p>
                                    <div id="walletBalanceWarning" class="alert alert-warning d-none mt-3 mb-0 py-2 px-3"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                {{ $wallet->exists ? 'Update Wallet Entry' : 'Add Wallet Entry' }}
                            </button>
                            <a href="{{ route('wallets.index', array_filter(['user_id' => $cancelUserId])) }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
