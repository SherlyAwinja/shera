<?php

namespace App\Services\Front;

use App\Models\Wallet;
use Illuminate\Support\Collection;

class WalletService
{
    public function currentBalanceForUser(int $userId): float
    {
        if ($userId <= 0) {
            return 0.0;
        }

        return round(
            (float) Wallet::query()
                ->where('user_id', $userId)
                ->effective()
                ->sum('signed_amount'),
            2
        );
    }

    public function recentEntriesForUser(int $userId, int $limit = 12): Collection
    {
        if ($userId <= 0) {
            return collect();
        }

        return Wallet::query()
            ->where('user_id', $userId)
            ->latest('created_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    public function normalizeRequestedAmount(float $requestedAmount, float $availableBalance, float $payableAmount): array
    {
        $requestedAmount = round(max($requestedAmount, 0), 2);
        $availableBalance = round(max($availableBalance, 0), 2);
        $payableAmount = round(max($payableAmount, 0), 2);
        $appliedAmount = round(min($requestedAmount, $availableBalance, $payableAmount), 2);

        return [
            'requested_amount' => $requestedAmount,
            'available_balance' => $availableBalance,
            'payable_amount' => $payableAmount,
            'applied_amount' => $appliedAmount,
            'was_adjusted' => abs($appliedAmount - $requestedAmount) > 0.00001,
        ];
    }

    public function formatAmount(float $amount): string
    {
        return 'KSH.' . number_format($amount, 2);
    }
}
