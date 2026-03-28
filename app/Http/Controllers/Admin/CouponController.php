<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\CouponService;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\Coupon;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use App\Models\ColumnPreference;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CouponController extends Controller
{
    protected $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
    }

    // Index
    public function index()
    {
        Session::put('page', 'coupons');

        $result = $this->couponService->coupons();

        if ($result['status'] === 'error') {
            return redirect('admin/dashboard')->with('error_message', $result['message']);
        }

        $coupons = $result['coupons'];
        $couponsModule = $result['couponsModule'];

        $columnPrefs = ColumnPreference::where('admin_id', Auth::guard('admin')->id())
            ->where('table_name', 'coupons')
            ->first();

        $couponsSavedOrder = $columnPrefs ? json_decode($columnPrefs->column_order, true) : null;
        $couponsHiddenCols = $columnPrefs ? json_decode($columnPrefs->hidden_columns, true) : [];

        return view('admin.coupons.index', compact(
            'coupons',
            'couponsModule',
            'couponsSavedOrder',
            'couponsHiddenCols'
        ));
    }

    // Show create form
    public function create()
    {
        Session::put('page', 'coupons');

        $title = 'Add Coupon';
        $coupon = new Coupon();

        // Normalize arrays for the form
        $coupon->categories = [];
        $coupon->brands = [];
        $coupon->users = [];
        $coupon->coupon_option = 'Automatic';
        $coupon->coupon_type = 'Multiple';
        $coupon->amount_type = 'percentage';
        $coupon->visible = 1;
        $coupon->status = 1;

        $categories = Category::getCategories('Admin'); // nested array
        $brands = Brand::orderBy('name')->pluck('name', 'id')->toArray();
        $users = User::select('email')->get()->toArray();

        $selCats = [];
        $selBrands = [];
        $selUsers = [];

        return view('admin.coupons.add_edit_coupon', compact(
            'title',
            'coupon',
            'categories',
            'brands',
            'users',
            'selCats',
            'selBrands',
            'selUsers'
        ));
    }

    // Store coupon
    public function store(CouponRequest $request)
    {
        $data = $request->validated();
        $message = $this->couponService->addEditCoupon($data);

        return redirect()->route('coupons.index')->with('success_message', $message);
    }

    // Show edit form
    public function edit($id)
    {
        Session::put('page', 'coupons');

        $title = 'Edit Coupon';
        $coupon = Coupon::findOrFail($id);

        // Normalize JSON arrays into PHP arrays for the form
        $coupon->categories = $coupon->categories ? (is_array($coupon->categories) ? $coupon->categories : json_decode($coupon->categories, true)) : [];
        $coupon->brands = $coupon->brands ? (is_array($coupon->brands) ? $coupon->brands : json_decode($coupon->brands, true)) : [];
        $coupon->users = $coupon->users ? (is_array($coupon->users) ? $coupon->users : json_decode($coupon->users, true)) : [];

        $categories = Category::getCategories('Admin');
        $brands = Brand::orderBy('name')->pluck('name', 'id')->toArray();
        $users = User::select('email')->get()->toArray();

        $selCats = $coupon->categories;
        $selBrands = $coupon->brands;
        $selUsers = $coupon->users;

        return view('admin.coupons.add_edit_coupon', compact(
            'title',
            'coupon',
            'categories',
            'brands',
            'users',
            'selCats',
            'selBrands',
            'selUsers'
        ));
    }

    // Update coupon
    public function update(CouponRequest $request, $id)
    {
        $data = $request->validated();
        $data['id'] = $id;

        $message = $this->couponService->addEditCoupon($data);

        return redirect()->route('coupons.index')->with('success_message', $message);
    }

    // Ajax status toggle
    public function updateCouponStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            $status = $data['status'] === 'Active' ? 0 : 1;

            Coupon::where('id', $data['coupon_id'])->update(['status' => $status]);

            return response()->json([
                'status' => $status,
                'coupon_id' => $data['coupon_id']
            ]);
        }
    }

    // Destroy coupon
    public function destroy($id)
    {
        Coupon::where('id', $id)->delete();
        return redirect()->back()->with('success_message', 'Coupon deleted successfully');
    }
}
