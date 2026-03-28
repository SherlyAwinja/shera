<?php

namespace App\Services\Front;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class CouponService
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function applyCoupon(string $rawCode): array
    {
        $code = strtoupper(trim($rawCode));

        // Empty coupon
        if ($code === '') {
            Session::forget(['applied_coupon', 'applied_coupon_id', 'applied_coupon_discount']);

            return $this->response(false, 'Coupon code is required.');
        }

        // Get coupon
        $coupon = Coupon::where('coupon_code', $code)
            ->where('status', 1)
            ->first();

        if (!$coupon) {
            Session::forget(['applied_coupon', 'applied_coupon_id', 'applied_coupon_discount']);
            return $this->response(false, 'Invalid coupon code.');
        }

        // Expiry check
        if (!empty($coupon->expiry_date)) {
            try {
                if (Carbon::now()->gt(Carbon::parse($coupon->expiry_date)->endOfDay())) {
                    Session::forget(['applied_coupon', 'applied_coupon_id', 'applied_coupon_discount']);
                    return $this->response(false, 'Coupon has expired.');
                }
            } catch (\Exception $e) {
                return $this->response(false, 'Invalid coupon expiry.');
            }
        }

        $cart = $this->cartService->getCart();

        // Usage limit
        if (!empty($coupon->total_usage_limit) &&
            $coupon->used_count >= $coupon->total_usage_limit) {
            return $this->response(false, 'Coupon usage limit reached.');
        }

        // Per-user limit
        if (!empty($coupon->usage_limit_per_user) && Auth::check()) {
            $userUses = DB::table('coupon_usages')
                ->where('coupon_id', $coupon->id)
                ->where('user_id', Auth::id())
                ->count();

            if ($userUses >= $coupon->usage_limit_per_user) {
                return $this->response(false, 'You have already used this coupon maximum times.');
            }
        }

        $subtotal = (float) ($cart['subtotal'] ?? 0);
        $cartQty = array_sum(array_column($cart['items'], 'qty'));

        // Min cart value
        if (!empty($coupon->min_cart_value) && $subtotal < $coupon->min_cart_value) {
            return $this->response(false, 'Cart total too low for this coupon.');
        }

        // Max cart value
        if (!empty($coupon->max_cart_value) && $subtotal > $coupon->max_cart_value) {
            return $this->response(false, 'Cart total exceeds allowed amount.');
        }

        // Min qty
        if (!empty($coupon->min_qty) && $cartQty < $coupon->min_qty) {
            return $this->response(false, 'Not enough items for this coupon.');
        }

        // Max qty
        if (!empty($coupon->max_qty) && $cartQty > $coupon->max_qty) {
            return $this->response(false, 'Too many items for this coupon.');
        }

        // Category check
        if (!empty($coupon->categories)) {
            $allowed = is_array($coupon->categories)
                ? $coupon->categories
                : json_decode($coupon->categories, true);

            $match = false;

            foreach ($cart['items'] as $item) {
                $product = Product::find($item['product_id']);
                if ($product && in_array($product->category_id, $allowed)) {
                    $match = true;
                    break;
                }
            }

            if (!$match) {
                return $this->response(false, 'Coupon not valid for cart items.');
            }
        }

        // Compute discount
        $discount = 0;

        if (strtolower($coupon->amount_type) === 'percentage') {
            $discount = ($subtotal * $coupon->amount) / 100;

            if (!empty($coupon->max_discount)) {
                $discount = min($discount, $coupon->max_discount);
            }
        } else {
            $discount = min($coupon->amount, $subtotal);
        }

        // Save to session
        Session::put('applied_coupon', $coupon->coupon_code);
        Session::put('applied_coupon_id', $coupon->id);
        Session::put('applied_coupon_discount', $discount);

        $cart = $this->cartService->getCart();

        return $this->response(true, 'Coupon applied successfully.');
    }

    public function removeCoupon(): array
    {
        Session::forget(['applied_coupon', 'applied_coupon_id', 'applied_coupon_discount']);

        return $this->response(true, 'Coupon removed.');
    }

    private function response($status, $message)
    {
        $cart = $this->cartService->getCart();

        return array_merge([
            'status' => $status,
            'message' => $message,
        ], $this->cartService->responsePayload($cart));
    }
}
