@extends('admin.layout.layout')

@section('content')
<main class="app-main">
    <div class="app-content-header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <h3 class="mb-0">Wallet Management</h3>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-end">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Wallets</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="app-content">
        <div class="container-fluid">
            <div class="card mb-4">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h3 class="card-title mb-1">Wallet Credits and Debits</h3>
                        <p class="text-muted mb-0 small">Running balances are calculated per user using active, unexpired entries in chronological order.</p>
                    </div>

                    @if(($walletsModule['edit_access'] ?? 0) == 1 || ($walletsModule['full_access'] ?? 0) == 1)
                        <a href="{{ route('wallets.create', array_filter(['user_id' => $selectedUserId])) }}" class="btn btn-primary">
                            Add Wallet Entry
                        </a>
                    @endif
                </div>

                <div class="card-body">
                    @if(Session::has('success_message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong>Success:</strong> {{ Session::get('success_message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('wallets.index') }}" class="row g-3 align-items-end mb-4">
                        <div class="col-md-5">
                            <label for="walletFilterUser" class="form-label">Filter by User</label>
                            <select name="user_id" id="walletFilterUser" class="form-select">
                                <option value="">All users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ (string) $selectedUserId === (string) $user->id ? 'selected' : '' }}>
                                        {{ $user->name ?: 'User #' . $user->id }} ({{ $user->email }}) - Balance: KES {{ number_format((float) ($user->wallet_balance ?? 0), 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-outline-primary">Apply Filter</button>
                        </div>
                        <div class="col-md-auto">
                            <a href="{{ route('wallets.index') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </form>

                    @if($selectedUser)
                        <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <strong>{{ $selectedUser->name ?: 'User #' . $selectedUser->id }}</strong>
                                <span class="text-muted">({{ $selectedUser->email }})</span>
                            </div>
                            <div class="fw-semibold">
                                Live Balance:
                                <span id="selectedUserLiveBalance" data-user-id="{{ $selectedUser->id }}">
                                    KES {{ number_format((float) $selectedUserBalance, 2) }}
                                </span>
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table id="wallets" class="table table-bordered table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Amount</th>
                                    <th>Signed Amount</th>
                                    <th>Running Balance</th>
                                    <th>Live Balance</th>
                                    <th>Details</th>
                                    <th>Expiry Date</th>
                                    <th>Status</th>
                                    <th>Created On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($wallets as $wallet)
                                    <tr data-wallet-id="{{ $wallet->id }}" data-user-id="{{ $wallet->user_id }}">
                                        <td>{{ $wallet->id }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $wallet->user->name ?: 'User #' . $wallet->user_id }}</div>
                                            <div class="small text-muted">{{ $wallet->user->email }}</div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $wallet->action === 'credit' ? 'text-bg-success' : 'text-bg-danger' }}">
                                                {{ ucfirst($wallet->action) }}
                                            </span>
                                        </td>
                                        <td data-order="{{ (float) $wallet->amount }}">
                                            KES {{ number_format((float) $wallet->amount, 2) }}
                                        </td>
                                        <td data-order="{{ (float) $wallet->signed_amount }}" class="{{ $wallet->signed_amount >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $wallet->signed_amount >= 0 ? '+' : '-' }}KES {{ number_format(abs((float) $wallet->signed_amount), 2) }}
                                        </td>
                                        <td class="wallet-running-balance fw-semibold" data-order="{{ (float) $wallet->running_balance }}">
                                            KES {{ number_format((float) $wallet->running_balance, 2) }}
                                        </td>
                                        <td class="wallet-live-balance" data-order="{{ (float) $wallet->current_balance }}">
                                            KES {{ number_format((float) $wallet->current_balance, 2) }}
                                        </td>
                                        <td>
                                            {{ \Illuminate\Support\Str::limit($wallet->description ?: 'Manual adjustment', 60) }}
                                        </td>
                                        <td data-order="{{ optional($wallet->expiry_date)->timestamp ?? 32503680000 }}">
                                            @if($wallet->expiry_date)
                                                {{ $wallet->expiry_date->format('F j, Y') }}
                                            @else
                                                <span class="text-muted">No expiry</span>
                                            @endif
                                        </td>
                                        <td class="wallet-status-cell">
                                            <span class="badge wallet-status-text {{ $wallet->status ? 'text-bg-success' : 'text-bg-secondary' }}">
                                                {{ $wallet->status ? 'Active' : 'Inactive' }}
                                            </span>
                                            @if($wallet->is_expired)
                                                <div class="mt-1">
                                                    <span class="badge text-bg-warning wallet-expired-badge">Expired</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td data-order="{{ optional($wallet->created_at)->timestamp }}">
                                            {{ optional($wallet->created_at)->format('F j, Y, g:i a') }}
                                        </td>
                                        <td>
                                            @if(($walletsModule['edit_access'] ?? 0) == 1 || ($walletsModule['full_access'] ?? 0) == 1)
                                                <a href="javascript:void(0)"
                                                   class="updateWalletStatus"
                                                   data-wallet-id="{{ $wallet->id }}"
                                                   title="{{ $wallet->status ? 'Disable wallet entry' : 'Enable wallet entry' }}"
                                                   style="color: {{ $wallet->status ? '#3f6ed3' : 'grey' }};">
                                                    <i class="fas fa-toggle-{{ $wallet->status ? 'on' : 'off' }}" data-status="{{ $wallet->status ? 'Active' : 'Inactive' }}"></i>
                                                </a>

                                                &nbsp;&nbsp;

                                                <a href="{{ route('wallets.edit', $wallet->id) }}" title="Edit wallet entry">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif

                                            @if(($walletsModule['full_access'] ?? 0) == 1)
                                                &nbsp;&nbsp;

                                                <form action="{{ route('wallets.destroy', $wallet->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="button"
                                                            class="confirmDelete"
                                                            data-module="wallet"
                                                            data-id="{{ $wallet->id }}"
                                                            title="Delete wallet entry"
                                                            style="border:none; background:none; color:#3f6ed3;">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($wallets->isEmpty())
                        <div class="text-center text-muted py-4 border border-top-0 rounded-bottom">
                            No wallet entries found for the selected scope.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
