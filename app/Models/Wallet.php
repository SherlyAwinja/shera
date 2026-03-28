<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    public const ACTION_CREDIT = 'credit';
    public const ACTION_DEBIT = 'debit';

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

        return $query
            ->where('status', true)
            ->where(function (Builder $innerQuery) use ($today) {
                $innerQuery
                    ->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', $today);
            });
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
}
