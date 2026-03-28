<?php

namespace App\Services\Admin;

use App\Models\AdminsRole;
use App\Models\Coupon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CouponService
{
    public function Coupons()
    {
        $admin = Auth::guard('admin')->user();
        $coupons = Coupon::orderBy('id', 'DESC')->get();
        $couponsModuleCount = AdminsRole::where(['subadmin_id' => $admin->id, 'module' => 'coupons'])->count();

        if ($admin->role == "admin") {
            $couponsModule = [
                'view_access' => 1,
                'edit_access' => 1,
                'full_access' => 1,
            ];
        } elseif ($couponsModuleCount == 0) {
            return ['status' => 'error', 'message' => 'You do not have access to view coupons.'];
        } else {
            $couponsModule = AdminsRole::where(['subadmin_id' => $admin->id, 'module' => 'coupons'])->first()->toArray();
        }

        return [
            'status' => 'success',
            'coupons' => $coupons,
            'couponsModule' => $couponsModule
        ];
    }

    public function addEditCoupon(array $data): string
    {
        $coupon = !empty($data['id'])
            ? Coupon::findOrFail($data['id'])
            : new Coupon();

        $isNewCoupon = !$coupon->exists;

        $couponOption = ucfirst(strtolower((string) ($data['coupon_option'] ?? 'Automatic')));
        $couponType = ucfirst(strtolower((string) ($data['coupon_type'] ?? 'Multiple')));
        $amountType = strtolower((string) ($data['amount_type'] ?? 'percentage'));
        $couponCode = strtoupper(trim((string) ($data['coupon_code'] ?? '')));

        if ($couponCode === '' && $couponOption === 'Automatic') {
            $couponCode = $this->generateUniqueCouponCode();
        }

        $coupon->coupon_option = $couponOption;
        $coupon->coupon_code = $couponCode;
        $coupon->coupon_type = $couponType;
        $coupon->amount_type = $amountType;
        $coupon->amount = (float) ($data['amount'] ?? 0);
        $coupon->min_qty = $this->nullableInt($data['min_qty'] ?? null);
        $coupon->max_qty = $this->nullableInt($data['max_qty'] ?? null);
        $coupon->min_cart_value = $this->nullableFloat($data['min_cart_value'] ?? null);
        $coupon->max_cart_value = $this->nullableFloat($data['max_cart_value'] ?? null);
        $coupon->usage_limit_per_user = $this->nullableInt($data['usage_limit_per_user'] ?? null) ?? 0;
        $coupon->total_usage_limit = $this->nullableInt($data['total_usage_limit'] ?? null) ?? 0;
        $coupon->max_discount = $this->nullableFloat($data['max_discount'] ?? null);
        $coupon->expiry_date = !empty($data['expiry_date']) ? $data['expiry_date'] : null;
        $coupon->categories = $this->normalizeIntArray($data['categories'] ?? []);
        $coupon->brands = $this->normalizeIntArray($data['brands'] ?? []);
        $coupon->users = $this->normalizeStringArray($data['users'] ?? []);
        $coupon->visible = (int) ($data['visible'] ?? 0);
        $coupon->status = (int) ($data['status'] ?? 0);

        if ($isNewCoupon) {
            $coupon->used_count = 0;
        }

        $coupon->save();

        return $isNewCoupon
            ? 'Coupon added successfully'
            : 'Coupon updated successfully';
    }

    protected function generateUniqueCouponCode(int $length = 8): string
    {
        do {
            $code = strtoupper(Str::random($length));
        } while (Coupon::where('coupon_code', $code)->exists());

        return $code;
    }

    protected function normalizeIntArray($values): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($value) => is_numeric($value) ? (int) $value : null,
            Arr::wrap($values)
        ), fn ($value) => !is_null($value))));
    }

    protected function normalizeStringArray($values): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($value) => trim((string) $value),
            Arr::wrap($values)
        ))));
    }

    protected function nullableInt($value): ?int
    {
        if ($value === '' || is_null($value)) {
            return null;
        }

        return (int) $value;
    }

    protected function nullableFloat($value): ?float
    {
        if ($value === '' || is_null($value)) {
            return null;
        }

        return (float) $value;
    }
}
