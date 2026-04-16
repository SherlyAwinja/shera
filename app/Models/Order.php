<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const ORDER_STATUS_OPTIONS = [
        'placed' => 'Placed',
        'confirmed' => 'Confirmed',
        'processing' => 'Processing',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public const PAYMENT_STATUS_OPTIONS = [
        'pending' => 'Pending',
        'partially_paid' => 'Partially Paid',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ];

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
        'tracking_number',
        'tracking_link',
        'shipping_partner',
        'transaction_id',
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
        return $this->belongsTo(User::class, 'user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class);
    }


    public function address(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'user_address_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(UserAddress::class, 'user_address_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusAttribute(): string
    {
        return $this->getOrderStatusLabelAttribute();
    }

    public function getOrderStatusLabelAttribute(): string
    {
        return self::labelForOption(
            $this->attributes['order_status'] ?? null,
            self::ORDER_STATUS_OPTIONS,
            'Pending'
        );
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::labelForOption(
            $this->attributes['payment_status'] ?? null,
            self::PAYMENT_STATUS_OPTIONS,
            'Pending'
        );
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::formatOptionLabel($this->attributes['payment_method'] ?? null, 'N/A');
    }

    public function getSubtotalAttribute(): float
    {
        return (float) ($this->attributes['subtotal_amount'] ?? 0);
    }

    public function getDiscountAttribute(): float
    {
        return (float) ($this->attributes['discount_amount'] ?? 0);
    }

    public function getShippingAttribute(): float
    {
        return (float) ($this->attributes['shipping_amount'] ?? 0);
    }

    public function getTotalAttribute(): float
    {
        return (float) ($this->attributes['grand_total'] ?? 0);
    }

    public static function orderStatusOptions(): array
    {
        return self::ORDER_STATUS_OPTIONS;
    }

    public static function paymentStatusOptions(): array
    {
        return self::PAYMENT_STATUS_OPTIONS;
    }

    public static function formatOptionLabel(?string $value, string $fallback = 'N/A'): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            return $fallback;
        }

        return Str::headline(str_replace('_', ' ', $normalized));
    }

    private static function labelForOption(?string $value, array $options, string $fallback): string
    {
        $normalized = strtolower(trim((string) $value));

        if ($normalized === '') {
            return $fallback;
        }

        if ($normalized === 'complete') {
            $normalized = 'completed';
        }

        if ($normalized === 'canceled') {
            $normalized = 'cancelled';
        }

        return $options[$normalized] ?? self::formatOptionLabel($normalized, $fallback);
    }
}
