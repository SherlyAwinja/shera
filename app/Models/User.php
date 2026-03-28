<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'status',

        // Address fields (Kenya-friendly)
        'address_line1',
        'address_line2',
        'county',
        'sub_county',
        'estate',
        'landmark',
        'country',

        // Contact & business
        'phone',
        'business_name',

        // Admin flag
        'is_admin',
    ];

    /**
     * Hidden fields
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_change_requested_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'boolean',
        'is_admin' => 'boolean',
    ];

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return (bool) $this->status;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Check if user is vendor
     */
    public function isVendor(): bool
    {
        return $this->user_type === 'Vendor';
    }

    /**
     * Check if user is customer
     */
    public function isCustomer(): bool
    {
        return $this->user_type === 'Customer';
    }

    public function hasPendingEmailChange(): bool
    {
        return filled($this->pending_email);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function walletEntries(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(UserAddress::class)->where('is_default', true);
    }

    public function scopeWithWalletBalance(Builder $query): Builder
    {
        return $query->selectSub(
            Wallet::query()
                ->selectRaw('COALESCE(SUM(signed_amount), 0)')
                ->whereColumn('wallets.user_id', 'users.id')
                ->effective(),
            'wallet_balance'
        );
    }

    public function currentWalletBalance(): float
    {
        return (float) $this->walletEntries()->effective()->sum('signed_amount');
    }

    /**
     * Get full formatted address (useful for UI / delivery)
     */
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            $this->estate,
            $this->sub_county,
            $this->county,
            $this->landmark,
        ])->filter()->implode(', ');
    }
}
