<?php

namespace App\Services\Front;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use Throwable;

class CartService
{
    private const SESSION_WALLET_AMOUNT = 'applied_wallet_amount';
    private const SESSION_WALLET_USER_ID = 'applied_wallet_user_id';

    public function __construct(protected WalletService $walletService)
    {
    }

    protected function resolveSessionId(): string
    {
        $sessionId = Session::get('session_id');

        if (!$sessionId) {
            $sessionId = Session::getId();
            Session::put('session_id', $sessionId);
        }

        return $sessionId;
    }

    public function getCart(bool $lockForUpdate = false): array
    {
        return $this->buildCartFromRows($this->fetchCurrentCartRows($lockForUpdate));
    }

    public function summaryViewData(array $cart): array
    {
        return [
            'subtotal' => $cart['subtotal'],
            'discount' => $cart['discount'],
            'payable_before_wallet' => $cart['payable_before_wallet'],
            'wallet_balance' => $cart['wallet_balance'],
            'wallet_available_to_apply' => $cart['wallet_available_to_apply'],
            'requested_wallet_amount' => $cart['requested_wallet_amount'],
            'wallet_applied' => $cart['wallet_applied'],
            'remaining_payable' => $cart['remaining_payable'],
            'requires_payment_gateway' => $cart['requires_payment_gateway'],
            'can_checkout_with_wallet_only' => $cart['can_checkout_with_wallet_only'],
            'total' => $cart['total'],
        ];
    }

    public function responsePayload(array $cart): array
    {
        return [
            'items_html' => View::make('front.cart.ajax_cart_items', [
                'cartItems' => $cart['items'],
            ])->render(),
            'summary_html' => View::make('front.cart.ajax_cart_summary', $this->summaryViewData($cart))->render(),
            'totalCartItems' => array_sum(array_column($cart['items'], 'qty')),
            'cart' => [
                'subtotal' => $cart['subtotal'],
                'discount' => $cart['discount'],
                'payable_before_wallet' => $cart['payable_before_wallet'],
                'wallet_balance' => $cart['wallet_balance'],
                'wallet_available_to_apply' => $cart['wallet_available_to_apply'],
                'requested_wallet_amount' => $cart['requested_wallet_amount'],
                'wallet_applied' => $cart['wallet_applied'],
                'remaining_payable' => $cart['remaining_payable'],
                'requires_payment_gateway' => $cart['requires_payment_gateway'],
                'can_checkout_with_wallet_only' => $cart['can_checkout_with_wallet_only'],
                'total' => $cart['total'],
            ],
        ];
    }

    protected function resolveLinePrice(Cart $cartRow): float
    {
        $size = (string) ($cartRow->product_size ?? 'NA');
        $color = $this->normalizeColor($cartRow->product_color ?? null);

        if ($size !== '' && strtoupper($size) !== 'NA') {
            $attributePrice = Product::getAttributePrice($cartRow->product_id, $size, $color);

            if (($attributePrice['status'] ?? false) === true) {
                return (float) $attributePrice['final_price'];
            }
        }

        return (float) ($cartRow->product->final_price ?? $cartRow->product->product_price ?? 0);
    }

    protected function resolveLineImage(Cart $cartRow): string
    {
        if (!empty($cartRow->product->main_image)) {
            return $cartRow->product->main_image;
        }

        $firstImage = $cartRow->product->product_images->first();

        if ($firstImage && !empty($firstImage->image)) {
            return $firstImage->image;
        }

        return 'no-image.jpg';
    }

    protected function resolveLineColor(Cart $cartRow): ?string
    {
        $storedColor = $this->normalizeColor($cartRow->product_color ?? null);

        if ($storedColor !== null) {
            return $storedColor;
        }

        if ($cartRow->product && $cartRow->product->relationLoaded('productVariants')) {
            $variantColors = $this->uniqueLabels(
                $cartRow->product->productVariants->pluck('color')->all()
            );

            if (count($variantColors) === 1) {
                return $variantColors[0];
            }
        }

        $rawColors = (string) ($cartRow->product->product_color ?? '');
        $colors = collect(preg_split('/\s*,\s*/', $rawColors, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($color) => trim((string) $color))
            ->filter()
            ->values();

        return $colors->count() === 1 ? $colors->first() : null;
    }

    protected function fetchCurrentCartRows(bool $lockForUpdate = false): Collection
    {
        $query = $this->currentCartQuery()
            ->with(['product' => function ($query) {
                $query->with([
                    'product_images',
                    'category',
                    'productVariants',
                    'attributes' => function ($attributeQuery) {
                        $attributeQuery->where('status', 1)->orderBy('sort')->orderBy('id');
                    },
                ]);
            }])
            ->orderBy('id', 'desc');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    protected function buildCartFromRows(Collection $rows): array
    {
        $items = [];
        $subtotal = 0.0;

        foreach ($rows as $row) {
            $product = $row->product;
            if (!$product) {
                continue;
            }

            $selectedSize = $this->normalizeSize($row->product_size ?? 'NA');
            $selectedColor = $this->resolveLineColor($row);
            $variantEditor = $this->buildVariantEditorState($product, $selectedSize, $selectedColor);

            $unit = $this->resolveLinePrice($row);
            $fallbackImage = asset('front/images/products/no-image.jpg');

            if (!empty($product->main_image)) {
                $image = asset('product-image/medium/' . $product->main_image);
            } elseif (!empty($product->product_images[0]['image'])) {
                $image = asset('product-image/medium/' . $product->product_images[0]['image']);
            } else {
                $image = $fallbackImage;
            }

            $lineTotal = round($unit * (int) $row->product_qty, 2);
            $subtotal += $lineTotal;

            $items[] = [
                'cart_id' => $row->id,
                'product_id' => $product->id,
                'product_name' => $product->product_name,
                'product_url' => $product->product_url,
                'image' => $image,
                'size' => $selectedSize,
                'color' => $selectedColor,
                'qty' => (int) $row->product_qty,
                'unit_price' => $unit,
                'line_total' => $lineTotal,
                'category_id' => $product->category_id ?? null,
                'variant_options' => $variantEditor['variant_options'],
                'size_options' => $variantEditor['size_options'],
                'color_options' => $variantEditor['color_options'],
                'can_edit_size' => $variantEditor['can_edit_size'],
                'can_edit_color' => $variantEditor['can_edit_color'],
                'has_variant_controls' => $variantEditor['has_variant_controls'],
            ];
        }

        $couponDiscount = 0.0;
        $appliedCouponId = session('applied_coupon_id');

        if ($appliedCouponId) {
            $coupon = \App\Models\Coupon::find($appliedCouponId);

            if (
                !$coupon ||
                !$coupon->status ||
                ($coupon->expiry_date && now()->gt(\Carbon\Carbon::parse($coupon->expiry_date)->endOfDay()))
            ) {
                $this->clearCouponSession();
                $coupon = null;
            }

            if ($coupon) {
                $applicableAmount = $subtotal;

                if (!empty($coupon->categories)) {
                    $allowedCats = $coupon->categories;

                    if (is_string($allowedCats)) {
                        $decoded = json_decode($allowedCats, true);
                        if (is_array($decoded)) {
                            $allowedCats = $decoded;
                        }
                    }

                    if (is_array($allowedCats) && count($allowedCats)) {
                        $applicableAmount = 0;

                        foreach ($items as $item) {
                            if (!empty($item['category_id']) && in_array($item['category_id'], $allowedCats)) {
                                $applicableAmount += $item['line_total'];
                            }
                        }
                    }
                }

                if (!empty($coupon->min_cart_value) && $subtotal < (float) $coupon->min_cart_value) {
                    $this->clearCouponSession();
                    $coupon = null;
                }

                if ($coupon) {
                    if ($coupon->amount_type === 'percentage') {
                        $couponDiscount = round($applicableAmount * ($coupon->amount / 100), 2);
                    } else {
                        $couponDiscount = min((float) $coupon->amount, $applicableAmount);
                    }

                    if (!empty($coupon->max_discount)) {
                        $couponDiscount = min($couponDiscount, (float) $coupon->max_discount);
                    }

                    Session::put('applied_coupon_discount', $couponDiscount);
                }
            }
        }

        $subtotal = round((float) $subtotal, 2);
        $couponDiscount = round((float) $couponDiscount, 2);
        $payableBeforeWallet = round(max(0, $subtotal - $couponDiscount), 2);

        $walletBalance = 0.0;
        $walletAvailableToApply = 0.0;
        $requestedWalletAmount = 0.0;
        $walletApplied = 0.0;

        if (Auth::check()) {
            $currentUserId = (int) Auth::id();
            $walletSessionUserId = (int) Session::get(self::SESSION_WALLET_USER_ID, 0);

            if ($walletSessionUserId !== 0 && $walletSessionUserId !== $currentUserId) {
                $this->clearWalletSession();
            }

            $walletBalance = $this->walletService->currentBalanceForUser($currentUserId);
            $requestedWalletAmount = round((float) Session::get(self::SESSION_WALLET_AMOUNT, 0), 2);
            $walletAvailableToApply = round(min($walletBalance, $payableBeforeWallet), 2);

            if ($requestedWalletAmount > 0) {
                $walletApplied = round(min($requestedWalletAmount, $walletAvailableToApply), 2);
            }
        } else {
            $this->clearWalletSession();
        }

        $remainingPayable = round(max(0, $payableBeforeWallet - $walletApplied), 2);

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $couponDiscount,
            'payable_before_wallet' => $payableBeforeWallet,
            'wallet_balance' => $walletBalance,
            'wallet_available_to_apply' => $walletAvailableToApply,
            'requested_wallet_amount' => $requestedWalletAmount,
            'wallet_applied' => $walletApplied,
            'remaining_payable' => $remainingPayable,
            'requires_payment_gateway' => $remainingPayable > 0,
            'can_checkout_with_wallet_only' => $walletApplied > 0 && $remainingPayable == 0.0,
            'total' => $remainingPayable,
        ];
    }

    protected function buildCartResponse(bool $status, string $message, array $cart, array $extra = []): array
    {
        return array_merge([
            'status' => $status,
            'message' => $message,
        ], $this->responsePayload($cart), $extra);
    }

    protected function clearWalletSession(): void
    {
        Session::forget([
            self::SESSION_WALLET_AMOUNT,
            self::SESSION_WALLET_USER_ID,
        ]);
    }

    protected function clearCouponSession(): void
    {
        Session::forget([
            'applied_coupon',
            'applied_coupon_id',
            'applied_coupon_discount',
        ]);
    }

    public function addToCart($data)
    {
        $size = $this->normalizeSize($data['size'] ?? 'NA');
        $color = $this->normalizeColor($data['color'] ?? null);
        $qty = (int) $data['qty'];
        $replaceQty = filter_var($data['replace_qty'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $product = Product::query()->with('productVariants')->find((int) $data['product_id']);

        if (!$product) {
            return ['status' => false, 'message' => 'Product not found.'];
        }

        $selection = $this->resolveCanonicalSelection($product, $size, $color);

        if (!empty($selection['error'])) {
            return ['status' => false, 'message' => $selection['error']];
        }

        $size = $selection['size'];
        $color = $selection['color'];

        $cartQuery = $this->currentCartQuery()
            ->where('product_id', (int) $data['product_id'])
            ->where('product_size', $size);

        $cartItem = $this->applyColorScope($cartQuery, $color)->first();
        $targetQty = $cartItem
            ? ($replaceQty ? $qty : ((int) $cartItem->product_qty + $qty))
            : $qty;
        $availableStock = (int) ($selection['stock'] ?? $this->resolveAvailableStock($product, $size, $color));

        if ($availableStock >= 0 && $targetQty > $availableStock) {
            return [
                'status' => false,
                'message' => $availableStock > 0
                    ? 'Only ' . $availableStock . ' unit' . ($availableStock === 1 ? '' : 's') . ' available for the selected variant.'
                    : 'The selected variant is currently out of stock.',
            ];
        }

        if ($cartItem) {
            if ($replaceQty) {
                $cartItem->product_qty = $qty;
            } else {
                $cartItem->product_qty += $qty;
            }

            $cartItem->save();
        } else {
            $payload = [
                'product_id' => (int) $data['product_id'],
                'product_size' => $size,
                'product_color' => $color,
                'product_qty' => $qty,
            ];

            if (Auth::check()) {
                $payload['user_id'] = (int) Auth::id();
                $payload['session_id'] = $this->resolveSessionId();
            } else {
                $payload['session_id'] = $this->resolveSessionId();
            }

            Cart::create($payload);
        }

        return [
            'status' => true,
            'message' => $replaceQty ? 'Cart quantity updated!' : 'Product added to cart!',
            'totalCartItems' => totalCartItems(),
        ];
    }

    public function migrateGuestCartToUser(?string $guestSessionId, int $userId): void
    {
        $currentSessionId = Session::getId();
        Session::put('session_id', $currentSessionId);

        if (blank($guestSessionId) || $userId <= 0) {
            return;
        }

        $guestRows = Cart::query()
            ->where('session_id', $guestSessionId)
            ->where(function ($query) {
                $query->where('user_id', 0)
                    ->orWhereNull('user_id');
            })
            ->orderBy('id')
            ->get();

        if ($guestRows->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($guestRows, $userId, $currentSessionId) {
            foreach ($guestRows as $guestRow) {
                $size = filled($guestRow->product_size) ? $guestRow->product_size : 'NA';
                $color = $this->normalizeColor($guestRow->product_color ?? null);

                $existingUserQuery = Cart::query()
                    ->where('user_id', $userId)
                    ->where('product_id', $guestRow->product_id)
                    ->where('product_size', $size)
                    ->lockForUpdate();

                $existingUserRow = $this->applyColorScope($existingUserQuery, $color)->first();

                if ($existingUserRow) {
                    $existingUserRow->product_qty += (int) $guestRow->product_qty;
                    $existingUserRow->session_id = $currentSessionId;
                    $existingUserRow->save();

                    $guestRow->delete();
                    continue;
                }

                $guestRow->user_id = $userId;
                $guestRow->session_id = $currentSessionId;
                $guestRow->product_size = $size;
                $guestRow->product_color = $color;
                $guestRow->save();
            }
        });
    }

    public function updateItem(int $cartItemId, array $data): array
    {
        $qty = (int) ($data['qty'] ?? 0);

        $cartItem = $this->currentCartQuery()
            ->with(['product' => function ($query) {
                $query->with(['productVariants', 'attributes' => function ($attributeQuery) {
                    $attributeQuery->where('status', 1)->orderBy('sort')->orderBy('id');
                }]);
            }])
            ->find($cartItemId);

        if (!$cartItem) {
            return ['status' => false, 'message' => 'Item not found'];
        }

        if (!$cartItem->product) {
            return ['status' => false, 'message' => 'Product not found'];
        }

        $requestedSize = array_key_exists('size', $data)
            ? $this->normalizeSize($data['size'])
            : $this->normalizeSize($cartItem->product_size ?? 'NA');
        $requestedColor = array_key_exists('color', $data)
            ? $this->normalizeColor($data['color'] ?? null)
            : $this->normalizeColor($cartItem->product_color ?? null);

        $selection = $this->resolveCanonicalSelection($cartItem->product, $requestedSize, $requestedColor);

        if (!empty($selection['error'])) {
            return [
                'status' => false,
                'message' => $selection['error'],
            ];
        }

        $requestedSize = $this->normalizeSize($selection['size'] ?? $requestedSize);
        $requestedColor = $this->normalizeColor($selection['color'] ?? $requestedColor);
        $availableStock = (int) ($selection['stock'] ?? $this->resolveAvailableStock($cartItem->product, $requestedSize, $requestedColor));

        return DB::transaction(function () use ($cartItemId, $qty, $requestedSize, $requestedColor, $availableStock) {
            $cartItem = $this->currentCartQuery()
                ->whereKey($cartItemId)
                ->lockForUpdate()
                ->first();

            if (!$cartItem) {
                return ['status' => false, 'message' => 'Item not found'];
            }

            $existingVariantQuery = $this->currentCartQuery()
                ->where('product_id', $cartItem->product_id)
                ->where('product_size', $requestedSize)
                ->whereKeyNot($cartItem->id)
                ->lockForUpdate();

            $existingVariantLine = $this->applyColorScope($existingVariantQuery, $requestedColor)->first();
            $targetQty = $qty + (int) ($existingVariantLine->product_qty ?? 0);

            if ($availableStock >= 0 && $targetQty > $availableStock) {
                return [
                    'status' => false,
                    'message' => $availableStock > 0
                        ? 'Only ' . $availableStock . ' unit' . ($availableStock === 1 ? '' : 's') . ' available for this variant.'
                        : 'This variant is currently out of stock.',
                ];
            }

            if ($existingVariantLine) {
                $existingVariantLine->product_qty = $targetQty;
                $existingVariantLine->save();
                $cartItem->delete();

                return ['status' => true, 'message' => 'Cart updated'];
            }

            $cartItem->product_size = $requestedSize;
            $cartItem->product_color = $requestedColor;
            $cartItem->product_qty = $qty;
            $cartItem->save();

            return ['status' => true, 'message' => 'Cart updated'];
        });
    }

    public function updateQty($cartItemId, $qty)
    {
        return $this->updateItem((int) $cartItemId, [
            'qty' => (int) $qty,
        ]);
    }

    public function removeItem($cartItemId)
    {
        $cartItem = $this->currentCartQuery()->find($cartItemId);

        if (!$cartItem) {
            return ['status' => false, 'message' => 'Item not found'];
        }

        $cartItem->delete();

        return ['status' => true, 'message' => 'Item removed from cart'];
    }

    public function applyWallet(float $requestedAmount): array
    {
        $cart = $this->getCart();

        if (!Auth::check()) {
            return $this->buildCartResponse(false, 'Please log in to apply wallet credit.', $cart, [
                'auth_required' => true,
            ]);
        }

        if (empty($cart['items'])) {
            return $this->buildCartResponse(false, 'Your cart is empty.', $cart);
        }

        if ($requestedAmount <= 0) {
            return $this->buildCartResponse(false, 'Enter a valid wallet amount to apply.', $cart);
        }

        if ($cart['wallet_balance'] <= 0) {
            return $this->buildCartResponse(false, 'No active wallet balance is available on your account.', $cart);
        }

        if ($cart['payable_before_wallet'] <= 0) {
            $this->clearWalletSession();

            return $this->buildCartResponse(false, 'There is no remaining payable amount to cover with wallet credit.', $this->getCart());
        }

        $normalized = $this->walletService->normalizeRequestedAmount(
            $requestedAmount,
            $cart['wallet_balance'],
            $cart['payable_before_wallet']
        );

        if ($normalized['applied_amount'] <= 0) {
            return $this->buildCartResponse(false, 'Wallet credit cannot be applied to this cart right now.', $cart);
        }

        Session::put(self::SESSION_WALLET_AMOUNT, $normalized['requested_amount']);
        Session::put(self::SESSION_WALLET_USER_ID, (int) Auth::id());

        $updatedCart = $this->getCart();
        $message = $normalized['was_adjusted']
            ? 'Wallet credit adjusted to ' . $this->walletService->formatAmount($updatedCart['wallet_applied']) . ' based on your live balance and current cart total.'
            : 'Wallet credit of ' . $this->walletService->formatAmount($updatedCart['wallet_applied']) . ' applied successfully.';

        return $this->buildCartResponse(true, $message, $updatedCart);
    }

    public function removeWallet(): array
    {
        $this->clearWalletSession();

        return $this->buildCartResponse(true, 'Wallet credit removed from this cart.', $this->getCart());
    }

    public function checkoutPreview(): array
    {
        $cart = $this->getCart();

        if (empty($cart['items'])) {
            return $this->buildCartResponse(false, 'Your cart is empty.', $cart);
        }

        if (!Auth::check()) {
            return $this->buildCartResponse(false, 'Please log in to continue to checkout and use wallet credit.', $cart, [
                'auth_required' => true,
            ]);
        }

        if ($cart['can_checkout_with_wallet_only']) {
            return $this->buildCartResponse(
                true,
                'Wallet credit fully covers this cart. You can complete the order directly with your wallet balance.',
                $cart
            );
        }

        if ($cart['wallet_applied'] > 0) {
            return $this->buildCartResponse(
                true,
                'Wallet credit will cover ' . $this->walletService->formatAmount($cart['wallet_applied']) . '. The remaining ' . $this->walletService->formatAmount($cart['remaining_payable']) . ' should be collected by your payment gateway.',
                $cart
            );
        }

        return $this->buildCartResponse(
            true,
            'No wallet credit is applied yet. The current payable amount is ' . $this->walletService->formatAmount($cart['remaining_payable']) . '.',
            $cart
        );
    }

    public function completeWalletCheckout(): array
    {
        $cart = $this->getCart();

        if (!Auth::check()) {
            return $this->buildCartResponse(false, 'Please log in to complete a wallet checkout.', $cart, [
                'auth_required' => true,
            ]);
        }

        if (empty($cart['items'])) {
            return $this->buildCartResponse(false, 'Your cart is empty.', $cart);
        }

        if ($cart['wallet_applied'] <= 0) {
            return $this->buildCartResponse(false, 'Apply wallet credit before attempting a wallet checkout.', $cart);
        }

        if ($cart['remaining_payable'] > 0) {
            return $this->buildCartResponse(
                false,
                'Wallet credit covers only part of this cart. The remaining ' . $this->walletService->formatAmount($cart['remaining_payable']) . ' still needs payment gateway support.',
                $cart
            );
        }

        $walletEntry = null;

        try {
            DB::transaction(function () use (&$walletEntry) {
                $userId = (int) Auth::id();

                User::query()->whereKey($userId)->lockForUpdate()->firstOrFail();
                Wallet::query()->where('user_id', $userId)->lockForUpdate()->get();

                $lockedRows = $this->fetchCurrentCartRows(true);
                $lockedCart = $this->buildCartFromRows($lockedRows);

                if (empty($lockedCart['items'])) {
                    throw ValidationException::withMessages([
                        'cart' => 'Your cart is empty.',
                    ]);
                }

                if ($lockedCart['wallet_applied'] <= 0) {
                    throw ValidationException::withMessages([
                        'wallet' => 'Wallet credit is no longer applied to this cart.',
                    ]);
                }

                if ($lockedCart['remaining_payable'] > 0) {
                    throw ValidationException::withMessages([
                        'wallet' => 'Wallet credit now covers only part of this cart. Review the totals again before paying.',
                    ]);
                }

                $walletAmount = round((float) $lockedCart['wallet_applied'], 2);
                $liveBalance = $this->walletService->currentBalanceForUser($userId);

                if (($walletAmount - $liveBalance) > 0.00001) {
                    throw ValidationException::withMessages([
                        'wallet' => 'Your live wallet balance changed. Review the cart and reapply wallet credit.',
                    ]);
                }

                $walletEntry = Wallet::query()->create([
                    'user_id' => $userId,
                    'action' => Wallet::ACTION_DEBIT,
                    'amount' => $walletAmount,
                    'signed_amount' => $walletAmount * -1,
                    'description' => 'Wallet used to complete a cart checkout. Covered ' . $this->walletService->formatAmount($walletAmount) . ' on a cart payable amount of ' . $this->walletService->formatAmount($lockedCart['payable_before_wallet']) . '.',
                    'expiry_date' => null,
                    'status' => true,
                ]);

                if ($lockedRows->isNotEmpty()) {
                    Cart::query()->whereKey($lockedRows->pluck('id')->all())->delete();
                }

                $this->clearCouponSession();
                $this->clearWalletSession();
            });
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?: 'Unable to complete wallet checkout.';

            return $this->buildCartResponse(false, $message, $this->getCart());
        } catch (Throwable $throwable) {
            report($throwable);

            return $this->buildCartResponse(false, 'Unable to complete wallet checkout right now. Please try again.', $this->getCart());
        }

        return $this->buildCartResponse(
            true,
            'Order completed using wallet credit. A debit entry has been added to your wallet history.',
            $this->getCart(),
            [
                'completed_with_wallet' => true,
                'wallet_entry_id' => $walletEntry?->id,
            ]
        );
    }

    /**
     * Helper to get the current user's cart query (Auth or Session)
     */
    public function currentCartQuery()
    {
        if (Auth::check()) {
            return Cart::query()->where('user_id', (int) Auth::id());
        }

        return Cart::query()->where('session_id', $this->resolveSessionId());
    }

    protected function normalizeSize(mixed $size): string
    {
        $normalized = trim((string) $size);

        if ($normalized === '' || strtoupper($normalized) === 'NA') {
            return 'NA';
        }

        return $normalized;
    }

    protected function normalizeColor(mixed $color): ?string
    {
        $normalized = trim((string) $color);

        return $normalized === '' ? null : $normalized;
    }

    protected function parseProductColors(string $rawColors): array
    {
        return $this->uniqueLabels(
            preg_split('/\s*,\s*/', $rawColors, -1, PREG_SPLIT_NO_EMPTY) ?: []
        );
    }

    protected function buildVariantEditorState(Product $product, string $selectedSize, ?string $selectedColor): array
    {
        $variantOptions = $this->buildVariantMatrix($product, $selectedSize, $selectedColor);
        $sizeOptions = array_values(array_filter(
            $this->uniqueLabels(array_column($variantOptions, 'size')),
            fn (string $size) => strtoupper($size) !== 'NA'
        ));
        $colorOptions = $this->uniqueLabels(array_values(array_filter(
            array_column($variantOptions, 'color'),
            fn ($color) => $color !== null && trim((string) $color) !== ''
        )));

        return [
            'variant_options' => $variantOptions,
            'size_options' => $sizeOptions,
            'color_options' => $colorOptions,
            'can_edit_size' => count($sizeOptions) > 1,
            'can_edit_color' => count($colorOptions) > 1,
            'has_variant_controls' => count($sizeOptions) > 1 || count($colorOptions) > 1,
        ];
    }

    protected function buildVariantMatrix(Product $product, string $selectedSize, ?string $selectedColor): array
    {
        $product->loadMissing(['productVariants', 'attributes']);

        $options = [];
        $seen = [];

        if ($product->productVariants->isNotEmpty()) {
            foreach ($product->productVariants as $variant) {
                $size = $this->normalizeSize($variant->size);
                $color = $this->normalizeColor($variant->color);
                $key = strtolower($size . '|' . ($color ?? ''));

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $options[] = [
                    'size' => $size,
                    'color' => $color,
                    'stock' => max(0, (int) $variant->stock),
                ];
            }
        } else {
            $sizes = $this->uniqueLabels($product->attributes->pluck('size')->all());
            if (empty($sizes)) {
                $sizes = [$selectedSize];
            }

            $colors = $this->parseProductColors((string) ($product->product_color ?? ''));
            if (empty($colors)) {
                $colors = [$selectedColor];
            }

            foreach ($sizes as $sizeOption) {
                $normalizedSize = $this->normalizeSize($sizeOption);
                foreach ($colors as $colorOption) {
                    $normalizedColor = $this->normalizeColor($colorOption);
                    $key = strtolower($normalizedSize . '|' . ($normalizedColor ?? ''));

                    if (isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    $options[] = [
                        'size' => $normalizedSize,
                        'color' => $normalizedColor,
                        'stock' => max(0, (int) $this->resolveAvailableStock($product, $normalizedSize, $normalizedColor)),
                    ];
                }
            }
        }

        $selectedKey = strtolower($selectedSize . '|' . ($selectedColor ?? ''));
        if (!isset($seen[$selectedKey])) {
            $options[] = [
                'size' => $selectedSize,
                'color' => $selectedColor,
                'stock' => max(0, (int) $this->resolveAvailableStock($product, $selectedSize, $selectedColor)),
            ];
        }

        return $options;
    }

    protected function applyColorScope($query, ?string $color)
    {
        return $query->where(function ($colorQuery) use ($color) {
            if ($color === null) {
                $colorQuery->whereNull('product_color')
                    ->orWhere('product_color', '');

                return;
            }

            $colorQuery->where('product_color', $color);
        });
    }

    protected function resolveCanonicalSelection(Product $product, ?string $size, ?string $color): array
    {
        $product->loadMissing('productVariants');

        if ($product->productVariants->isEmpty()) {
            return [
                'size' => filled($size) ? (string) $size : 'NA',
                'color' => $this->normalizeColor($color),
                'stock' => $this->resolveAvailableStock($product, filled($size) ? (string) $size : 'NA', $this->normalizeColor($color)),
            ];
        }

        $normalizedSize = trim((string) $size);
        if (strtoupper($normalizedSize) === 'NA') {
            $normalizedSize = '';
        }

        $normalizedColor = $this->normalizeColor($color);
        $matchingVariants = $product->productVariants;

        if ($normalizedColor !== null) {
            $matchingVariants = $matchingVariants->filter(function ($variant) use ($normalizedColor) {
                return strtolower(trim((string) $variant->color)) === strtolower($normalizedColor);
            })->values();
        }

        if ($normalizedSize !== '') {
            $matchingVariants = $matchingVariants->filter(function ($variant) use ($normalizedSize) {
                return strtolower(trim((string) $variant->size)) === strtolower($normalizedSize);
            })->values();
        }

        if ($matchingVariants->isEmpty()) {
            return [
                'error' => ($normalizedColor !== null || $normalizedSize !== '')
                    ? 'The selected size and color combination is unavailable.'
                    : 'Choose a valid product variant before adding this item to the cart.',
            ];
        }

        if ($matchingVariants->count() > 1) {
            $matchingColors = $this->uniqueLabels($matchingVariants->pluck('color')->all());
            $matchingSizes = $this->uniqueLabels($matchingVariants->pluck('size')->all());

            if ($normalizedColor === null && count($matchingColors) > 1) {
                return ['error' => 'Select a color before adding this item to the cart.'];
            }

            if ($normalizedSize === '' && count($matchingSizes) > 1) {
                return ['error' => 'Select a size before adding this item to the cart.'];
            }

            return ['error' => 'Choose a specific size and color combination before adding this item to the cart.'];
        }

        $variant = $matchingVariants->first();

        return [
            'size' => (string) $variant->size,
            'color' => $this->normalizeColor($variant->color),
            'stock' => max(0, (int) $variant->stock),
        ];
    }

    protected function resolveAvailableStock(Product $product, string $size, ?string $color): ?int
    {
        $product->loadMissing('productVariants');

        if ($product->productVariants->isNotEmpty()) {
            $matchingVariant = $product->productVariants->first(function ($variant) use ($size, $color) {
                $sizeMatches = strtolower(trim((string) $variant->size)) === strtolower(trim((string) $size));

                if (!$sizeMatches) {
                    return false;
                }

                if ($color === null) {
                    return true;
                }

                return strtolower(trim((string) $variant->color)) === strtolower(trim((string) $color));
            });

            return $matchingVariant ? max(0, (int) $matchingVariant->stock) : 0;
        }

        if ($size !== '' && strtoupper($size) !== 'NA') {
            $attributePrice = Product::getAttributePrice($product->id, $size, $color);

            if (($attributePrice['status'] ?? false) === true) {
                return max(0, (int) ($attributePrice['stock'] ?? 0));
            }
        }

        return max(0, (int) ($product->stock ?? 0));
    }

    protected function uniqueLabels(array $values): array
    {
        $labels = [];
        $seen = [];

        foreach ($values as $value) {
            $label = trim((string) $value);

            if ($label === '') {
                continue;
            }

            $normalized = strtolower($label);

            if (isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $labels[] = $label;
        }

        return $labels;
    }
}
