<?php

namespace App\Services\Front;

use App\Mail\OrderPlaced;
use App\Models\Order;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly WalletService $walletService
    ) {
    }

    public function createOrderFromCart(
        Request $request,
        UserAddress $selectedAddress,
        array $validated,
        callable $checkoutSummaryResolver
    ): Order {
        DB::beginTransaction();

        try {
            $user = $request->user();

            if (! $user instanceof User) {
                throw new \RuntimeException('Authenticated user is required to place an order.');
            }

            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            Wallet::query()->where('user_id', $user->id)->lockForUpdate()->get();

            $lockedCart = $this->cartService->getCart(true);
            $lockedSummary = $checkoutSummaryResolver($lockedCart, $selectedAddress);
            $lockedPaymentOption = collect($lockedSummary['paymentMethods'] ?? [])
                ->firstWhere('code', $validated['payment_method']);

            if (empty($lockedSummary['cart']['items'])) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart is empty. Add products before continuing to payment.',
                ]);
            }

            if (! ($lockedSummary['canProceed'] ?? false)) {
                throw ValidationException::withMessages([
                    'address' => 'Choose a valid delivery address before continuing to payment.',
                ]);
            }

            if (! $lockedPaymentOption || ! ($lockedPaymentOption['enabled'] ?? false)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Choose an available payment method before placing the order.',
                ]);
            }

            $cartRows = $this->cartService->currentCartQuery()
                ->with(['product' => function ($query) {
                    $query->with(['product_images']);
                }])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $order = Order::query()->create($this->buildOrderAttributes(
                $user,
                $selectedAddress,
                $lockedSummary,
                $validated['payment_method']
            ));

            $order->items()->createMany($this->buildOrderItemsPayload($cartRows));

            $walletAppliedAmount = round((float) ($lockedSummary['cart']['wallet_applied'] ?? 0), 2);

            if ($walletAppliedAmount > 0) {
                $liveBalance = $this->walletService->currentBalanceForUser((int) $user->id);

                if (($walletAppliedAmount - $liveBalance) > 0.00001) {
                    throw ValidationException::withMessages([
                        'wallet' => 'Your live wallet balance changed. Review the checkout totals and apply wallet credit again.',
                    ]);
                }

                Wallet::query()->create([
                    'user_id' => $user->id,
                    'action' => Wallet::ACTION_DEBIT,
                    'amount' => $walletAppliedAmount,
                    'signed_amount' => $walletAppliedAmount * -1,
                    'description' => 'Wallet applied to order ' . $order->order_number . '. Covered ' . $this->walletService->formatAmount($walletAppliedAmount) . ' during checkout.',
                    'expiry_date' => null,
                    'status' => true,
                ]);
            }

            if ($cartRows->isNotEmpty()) {
                $this->cartService->currentCartQuery()
                    ->whereKey($cartRows->pluck('id')->all())
                    ->delete();
            }

            $this->clearCheckoutState($request);

            DB::commit();
        } catch (Throwable $throwable) {
            DB::rollBack();
            throw $throwable;
        }

        $order->load(['items.product', 'user', 'address']);

        if (strtolower((string) $order->payment_method) === 'cod' && filled($order->user?->email)) {
            try {
                Mail::to($order->user->email)->queue(new OrderPlaced($order));
            } catch (Throwable $e) {
                Log::error($e->getMessage());
            }
        }

        return $order;
    }

    protected function buildOrderAttributes(
        User $user,
        UserAddress $selectedAddress,
        array $summary,
        string $paymentMethod
    ): array {
        $shippingQuote = $summary['shippingQuote'] ?? [];
        $walletAppliedAmount = round((float) ($summary['cart']['wallet_applied'] ?? 0), 2);
        $grandTotal = round((float) ($summary['grandTotal'] ?? 0), 2);
        $paymentStatus = $grandTotal == 0.0
            ? 'paid'
            : ($walletAppliedAmount > 0 ? 'partially_paid' : 'pending');

        return [
            'user_id' => $user->id,
            'user_address_id' => $selectedAddress->id,
            'order_uuid' => (string) Str::uuid(),
            'order_number' => $this->generateOrderNumber(),
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'order_status' => 'placed',
            'currency' => 'KSH',
            'items_count' => collect($summary['cartItems'] ?? [])->sum('qty'),
            'subtotal_amount' => round((float) ($summary['cart']['subtotal'] ?? 0), 2),
            'discount_amount' => round((float) ($summary['cart']['discount'] ?? 0), 2),
            'wallet_applied_amount' => $walletAppliedAmount,
            'shipping_amount' => round((float) ($summary['shippingAmount'] ?? 0), 2),
            'grand_total' => $grandTotal,
            'address_label' => $selectedAddress->label,
            'recipient_name' => (string) $selectedAddress->recipient_name,
            'recipient_phone' => (string) $selectedAddress->recipient_phone,
            'email' => $user->email,
            'country' => (string) $selectedAddress->country,
            'county' => $selectedAddress->county,
            'sub_county' => $selectedAddress->sub_county,
            'address_line1' => (string) $selectedAddress->address_line1,
            'address_line2' => $selectedAddress->address_line2,
            'estate' => $selectedAddress->estate,
            'landmark' => $selectedAddress->landmark,
            'pincode' => (string) $selectedAddress->pincode,
            'shipping_zone' => $shippingQuote['zone'] ?? null,
            'shipping_eta' => $shippingQuote['eta'] ?? null,
            'shipping_quote' => $shippingQuote,
            'tracking_link' => '',
            'placed_at' => now(),
        ];
    }

    protected function buildOrderItemsPayload(Collection $cartRows): array
    {
        return $cartRows->map(function ($cartRow) {
            $product = $cartRow->product;
            $image = $product && filled($product->main_image)
                ? asset('product-image/medium/' . $product->main_image)
                : asset('front/images/products/no-image.jpg');

            return [
                'product_id' => $product?->id,
                'product_name' => $product?->product_name ?: 'Product',
                'product_code' => $product?->product_code,
                'product_url' => $product?->product_url,
                'product_image' => $image,
                'size' => $cartRow->product_size,
                'color' => $cartRow->product_color,
                'quantity' => (int) $cartRow->product_qty,
                'unit_price' => round((float) $this->resolveOrderItemUnitPrice($cartRow), 2),
                'line_total' => round((float) $this->resolveOrderItemUnitPrice($cartRow) * (int) $cartRow->product_qty, 2),
            ];
        })->values()->all();
    }

    protected function resolveOrderItemUnitPrice($cartRow): float
    {
        $product = $cartRow->product;
        $size = (string) ($cartRow->product_size ?? 'NA');

        if ($product && $size !== '' && strtoupper($size) !== 'NA') {
            $attributePrice = $product::getAttributePrice($product->id, $size, $cartRow->product_color);

            if (($attributePrice['status'] ?? false) === true) {
                return (float) ($attributePrice['final_price'] ?? $attributePrice['product_price'] ?? 0);
            }
        }

        return (float) ($product?->final_price ?? $product?->product_price ?? 0);
    }

    protected function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'SHR-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    protected function clearCheckoutState(Request $request): void
    {
        $request->session()->forget([
            'applied_wallet_amount',
            'applied_wallet_user_id',
            'applied_coupon',
            'applied_coupon_id',
            'applied_coupon_discount',
        ]);
    }
}
