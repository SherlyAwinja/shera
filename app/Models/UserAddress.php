<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'label',
        'full_name',
        'phone',
        'address_line1',
        'address_line2',
        'country',
        'county',
        'sub_county',
        'estate',
        'landmark',
        'pincode',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            $this->estate,
            $this->sub_county,
            $this->county,
            $this->pincode,
            $this->country,
            $this->landmark,
        ])->filter()->implode(', ');
    }

    public function getRecipientNameAttribute(): ?string
    {
        return $this->full_name ?: $this->user?->name;
    }

    public function getRecipientPhoneAttribute(): ?string
    {
        return $this->phone ?: $this->user?->phone;
    }
}
