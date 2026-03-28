<?php

namespace App\Services\Admin;

use App\Models\AdminsRole;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function permissions(): array
    {
        return $this->resolveModulePermissions();
    }

    public function wallets(array $filters = []): array
    {
        $permissions = $this->permissions();
        if ($permissions['status'] === 'error') {
            return $permissions;
        }

        $selectedUserId = !empty($filters['user_id']) ? (int) $filters['user_id'] : null;

        $walletsQuery = Wallet::query()
            ->select('wallets.*')
            ->with('user:id,name,email')
            ->join('users', 'users.id', '=', 'wallets.user_id');

        if ($selectedUserId) {
            $walletsQuery->where('wallets.user_id', $selectedUserId);
        }

        $wallets = $walletsQuery
            ->orderBy('users.name')
            ->orderBy('wallets.created_at')
            ->orderBy('wallets.id')
            ->get();

        $wallets = $this->attachLedgerBalances($wallets);

        return [
            'status' => 'success',
            'wallets' => $wallets,
            'walletsModule' => $permissions['walletsModule'],
            'users' => $this->walletUsers(),
            'selectedUserId' => $selectedUserId,
            'selectedUserBalance' => $selectedUserId ? $this->currentBalance($selectedUserId) : null,
        ];
    }

    public function walletUsers(): Collection
    {
        return User::query()
            ->select('users.id', 'users.name', 'users.email')
            ->withWalletBalance()
            ->orderBy('name')
            ->orderBy('email')
            ->get();
    }

    public function saveWallet(array $data, ?int $walletId = null): Wallet
    {
        $wallet = $walletId ? Wallet::findOrFail($walletId) : new Wallet();
        $payload = $this->normalizePayload($data);
        $baseBalance = $this->currentBalance($payload['user_id'], $wallet->exists ? $wallet->id : null);
        $effectiveSignedAmount = $this->isEffectivePayload($payload) ? (float) $payload['signed_amount'] : 0.0;

        if (($baseBalance + $effectiveSignedAmount) < 0) {
            throw ValidationException::withMessages([
                'amount' => 'This debit exceeds the user\'s current live wallet balance.',
            ]);
        }

        $wallet->fill($payload);
        $wallet->save();

        return $wallet;
    }

    public function deleteWallet(int $walletId): array
    {
        $wallet = Wallet::findOrFail($walletId);
        $userId = (int) $wallet->user_id;
        $wallet->delete();

        return [
            'status' => 'success',
            'message' => 'Wallet entry deleted successfully.',
            'user_id' => $userId,
        ];
    }

    public function currentBalance(int $userId, ?int $excludeWalletId = null): float
    {
        $query = Wallet::query()
            ->where('user_id', $userId)
            ->effective();

        if ($excludeWalletId) {
            $query->whereKeyNot($excludeWalletId);
        }

        return round((float) $query->sum('signed_amount'), 2);
    }

    public function liveBalance(int $userId, ?int $excludeWalletId = null): array
    {
        $balance = $this->currentBalance($userId, $excludeWalletId);

        return [
            'balance' => $balance,
            'formatted_balance' => $this->formatCurrency($balance),
            'label' => $excludeWalletId
                ? 'Current live balance excluding this entry'
                : 'Current live balance',
            'help_text' => 'Projected balance updates as you change the action, amount, expiry date, or active state.',
        ];
    }

    public function toggleStatus(int $walletId): array
    {
        $wallet = Wallet::findOrFail($walletId);
        $wallet->status = !$wallet->status;
        $wallet->save();

        $ledger = $this->ledgerBreakdownForUser((int) $wallet->user_id);

        return [
            'status' => (int) $wallet->status,
            'wallet_id' => $wallet->id,
            'user_id' => (int) $wallet->user_id,
            'user_live_balance' => $ledger['current_balance'],
            'user_live_balance_formatted' => $this->formatCurrency($ledger['current_balance']),
            'rows' => $ledger['rows'],
        ];
    }

    public function ledgerBreakdownForUser(int $userId): array
    {
        $wallets = Wallet::query()
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $wallets = $this->attachLedgerBalances($wallets);
        $currentBalance = (float) ($wallets->last()->current_balance ?? 0.0);

        return [
            'current_balance' => $currentBalance,
            'rows' => $wallets->map(function (Wallet $wallet) {
                return [
                    'id' => $wallet->id,
                    'running_balance' => (float) $wallet->running_balance,
                    'running_balance_formatted' => $this->formatCurrency((float) $wallet->running_balance),
                ];
            })->values()->all(),
        ];
    }

    private function attachLedgerBalances(Collection $wallets): Collection
    {
        $runningBalances = [];

        foreach ($wallets as $wallet) {
            $userId = (int) $wallet->user_id;
            $runningBalances[$userId] = $runningBalances[$userId] ?? 0.0;
            $runningBalances[$userId] += $wallet->is_effective ? (float) $wallet->signed_amount : 0.0;
            $wallet->running_balance = round($runningBalances[$userId], 2);
        }

        foreach ($wallets as $wallet) {
            $wallet->current_balance = round($runningBalances[(int) $wallet->user_id] ?? 0.0, 2);
        }

        return $wallets;
    }

    private function normalizePayload(array $data): array
    {
        $action = strtolower((string) ($data['action'] ?? Wallet::ACTION_CREDIT));
        $amount = round((float) ($data['amount'] ?? 0), 2);

        return [
            'user_id' => (int) $data['user_id'],
            'action' => $action,
            'amount' => $amount,
            'signed_amount' => $action === Wallet::ACTION_DEBIT ? ($amount * -1) : $amount,
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'expiry_date' => filled($data['expiry_date'] ?? null)
                ? Carbon::parse($data['expiry_date'])->toDateString()
                : null,
            'status' => !empty($data['status']),
        ];
    }

    private function isEffectivePayload(array $payload): bool
    {
        if (empty($payload['status'])) {
            return false;
        }

        if (empty($payload['expiry_date'])) {
            return true;
        }

        return (string) $payload['expiry_date'] >= now()->toDateString();
    }

    private function formatCurrency(float $amount): string
    {
        return 'KES ' . number_format($amount, 2);
    }

    private function resolveModulePermissions(): array
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return ['status' => 'error', 'message' => 'Please log in again to access wallet management.'];
        }

        if (strtolower((string) $admin->role) === 'admin') {
            return [
                'status' => 'success',
                'walletsModule' => [
                    'view_access' => 1,
                    'edit_access' => 1,
                    'full_access' => 1,
                ],
            ];
        }

        $walletsModule = AdminsRole::where([
            'subadmin_id' => $admin->id,
            'module' => 'wallets',
        ])->first();

        if (!$walletsModule) {
            return ['status' => 'error', 'message' => 'You do not have permission to access wallets.'];
        }

        $walletsModule = $walletsModule->toArray();

        if (
            empty($walletsModule['view_access'])
            && empty($walletsModule['edit_access'])
            && empty($walletsModule['full_access'])
        ) {
            return ['status' => 'error', 'message' => 'You do not have permission to access wallets.'];
        }

        return ['status' => 'success', 'walletsModule' => $walletsModule];
    }
}
