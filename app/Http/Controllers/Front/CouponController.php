<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Front\CouponService;
use App\Models\Coupon;

class CouponController extends Controller
{
    protected $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    public function apply(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
        ]);
        $resp = $this->couponService->applyCoupon($request->input('coupon_code'));

        // Status code handled at controller level if desired
        return response()->json($resp, $resp['status'] ? 200 : 422);
    }

    public function remove(Request $request)
    {
        $resp = $this->couponService->removeCoupon();
        return response()->json($resp, $resp['status'] ? 200 : 422);
    }
}
