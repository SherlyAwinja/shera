<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    public const ACTION_CREDIT = 'credit';
    public const ACTION_DEBIT = 'debit';
    public const TOP_UP_REQUEST_PREFIX = 'Customer top-up request';

    protected $fillable = [
        'user_id',
        'action',
        'amount',
        'signed_amount',
        'description',
        'expiry_date',
        'status',
    ];

    protected $casts = [
        'amount' => 'float',
        'signed_amount' => 'float',
        'expiry_date' => 'date',
        'status' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeEffective(Builder $query): Builder
    {
        $today = now()->toDateString();
        $statusColumn = $query->getModel()->qualifyColumn('status');
        $expiryDateColumn = $query->getModel()->qualifyColumn('expiry_date');

        return $query
            ->where($statusColumn, true)
            ->where(function (Builder $innerQuery) use ($today, $expiryDateColumn) {
                $innerQuery
                    ->whereNull($expiryDateColumn)
                    ->orWhereDate($expiryDateColumn, '>=', $today);
            });
    }

    public function scopePendingTopUpRequests(Builder $query): Builder
    {
        $actionColumn = $query->getModel()->qualifyColumn('action');
        $statusColumn = $query->getModel()->qualifyColumn('status');
        $descriptionColumn = $query->getModel()->qualifyColumn('description');

        return $query
            ->where($actionColumn, self::ACTION_CREDIT)
            ->where($statusColumn, false)
            ->where($descriptionColumn, 'like', self::TOP_UP_REQUEST_PREFIX . '%');
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->copy()->endOfDay()->isPast();
    }

    public function getIsEffectiveAttribute(): bool
    {
        return (bool) $this->status && !$this->is_expired;
    }

    public function getIsPendingTopUpRequestAttribute(): bool
    {
        return $this->action === self::ACTION_CREDIT
            && !$this->status
            && str_starts_with((string) ($this->description ?? ''), self::TOP_UP_REQUEST_PREFIX);
    }
}
