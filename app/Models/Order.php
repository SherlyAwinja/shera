<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_address_id',
        'order_uuid',
        'order_number',
        'payment_method',
        'payment_status',
        'order_status',
        'currency',
        'items_count',
        'subtotal_amount',
        'discount_amount',
        'wallet_applied_amount',
        'shipping_amount',
        'grand_total',
        'address_label',
        'recipient_name',
        'recipient_phone',
        'email',
        'country',
        'county',
        'sub_county',
        'address_line1',
        'address_line2',
        'estate',
        'landmark',
        'pincode',
        'shipping_zone',
        'shipping_eta',
        'shipping_quote',
        'placed_at',
    ];

    protected $casts = [
        'shipping_quote' => 'array',
        'placed_at' => 'datetime',
        'subtotal_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'wallet_applied_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'user_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
