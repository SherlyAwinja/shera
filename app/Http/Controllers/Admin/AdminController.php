<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Filter;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Admin\LoginRequest;
use App\Services\Admin\AdminService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Admin\PasswordRequest;
use App\Http\Requests\Admin\DetailRequest;
use App\Http\Requests\Admin\SubadminRequest;
use App\Models\AdminsRole;
use App\Models\ColumnPreference;


class AdminController extends Controller
{

    protected $adminService;

    // Inject AdminService using constructor injection
    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Session::put('page', 'dashboard');
        $admin = Auth::guard('admin')->user();
        $isAdmin = strtolower((string) $admin->role) === 'admin';
        $dashboardModules = ['categories', 'products', 'brands', 'users', 'reviews', 'wallets', 'filters', 'filters_values', 'banners'];
        $roleModules = $isAdmin
            ? collect()
            : AdminsRole::where('subadmin_id', $admin->id)
                ->whereIn('module', $dashboardModules)
                ->get(['module', 'view_access', 'edit_access', 'full_access']);

        $canAccess = function (array $modules) use ($isAdmin, $roleModules) {
            if ($isAdmin) {
                return true;
            }

            foreach ($modules as $module) {
                $role = $roleModules->firstWhere('module', $module);
                if ($role && ($role->view_access || $role->edit_access || $role->full_access)) {
                    return true;
                }
            }

            return false;
        };

        $dashboardPermissions = [
            'dashboard' => true,
            'categories' => $canAccess(['categories']),
            'products' => $canAccess(['products']),
            'brands' => $canAccess(['brands']),
            'users' => $canAccess(['users']),
            'reviews' => $canAccess(['reviews']),
            'wallets' => $canAccess(['wallets']),
            'banners' => $canAccess(['banners']),
            'filters' => $canAccess(['filters', 'filters_values']),
            'subadmins' => $isAdmin,
        ];

        $categoriesCount = Category::count();
        $productsCount = Product::count();
        $brandsCount = Brand::count();
        $usersCount = User::count();
        $reviewsCount = Review::count();
        $walletEntriesCount = Wallet::count();
        $pendingReviewsCount = Review::where('status', 0)->count();
        $bannersCount = Banner::count();
        $filtersCount = Filter::count();
        $subadminsCount = Admin::where('role', 'subadmin')->count();
        $accessibleModulesCount = $isAdmin
            ? count($dashboardModules)
            : $roleModules
                ->filter(fn ($role) => $role->view_access || $role->edit_access || $role->full_access)
                ->pluck('module')
                ->unique()
                ->count();

        $approvedReviewsCount = Review::where('status', 1)->count();
        $inactiveProductsCount = Product::where('status', '!=', 1)->count();
        $featuredProductsCount = Product::where('is_featured', 'Yes')->count();
        $outOfStockProductsCount = Product::where('stock', '<=', 0)->count();
        $activeBrandsCount = Brand::where('status', 1)->count();

        $trendMonths = collect(range(5, 0))->map(function ($offset) {
            return Carbon::now()->startOfMonth()->subMonths($offset);
        });

        $trendStart = $trendMonths->first()->copy();
        $trendKeys = $trendMonths->map(fn ($month) => $month->format('Y-m'));
        $trendLabels = $trendMonths->map(fn ($month) => $month->format('M Y'))->values()->all();

        $usersByMonth = $dashboardPermissions['users']
            ? User::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as total")
                ->where('created_at', '>=', $trendStart)
                ->groupBy('month_key')
                ->pluck('total', 'month_key')
            : collect();

        $reviewsByMonth = $dashboardPermissions['reviews']
            ? Review::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COUNT(*) as total")
                ->where('created_at', '>=', $trendStart)
                ->groupBy('month_key')
                ->pluck('total', 'month_key')
            : collect();

        $cartQtyByMonth = $dashboardPermissions['products']
            ? Cart::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month_key, COALESCE(SUM(product_qty), 0) as total")
                ->where('created_at', '>=', $trendStart)
                ->groupBy('month_key')
                ->pluck('total', 'month_key')
            : collect();

        $topCategories = ($dashboardPermissions['categories'] || $dashboardPermissions['products'])
            ? DB::table('categories as categories')
                ->leftJoin('products as products', 'categories.id', '=', 'products.category_id')
                ->selectRaw('categories.name, COUNT(products.id) as product_count')
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('product_count')
                ->limit(6)
                ->get()
            : collect();

        $catalogMixLabels = [];
        $catalogMixSeries = [];

        if ($dashboardPermissions['products']) {
            $catalogMixLabels = [
                'Active Products',
                'Inactive Products',
                'Featured Products',
                'Out of Stock',
            ];

            $catalogMixSeries = [
                max((int) $productsCount - (int) $inactiveProductsCount, 0),
                (int) $inactiveProductsCount,
                (int) $featuredProductsCount,
                (int) $outOfStockProductsCount,
            ];
        }

        if ($dashboardPermissions['brands']) {
            $catalogMixLabels[] = 'Active Brands';
            $catalogMixSeries[] = (int) $activeBrandsCount;
        }

        $dashboardCharts = [
            'trend' => [
                'labels' => $trendLabels,
                'series' => [],
            ],
            'categories' => [
                'labels' => $topCategories->pluck('name')->values()->all(),
                'series' => $topCategories->pluck('product_count')->map(fn ($count) => (int) $count)->values()->all(),
            ],
            'reviewHealth' => [
                'labels' => ['Approved', 'Pending'],
                'series' => [(int) $approvedReviewsCount, (int) $pendingReviewsCount],
            ],
            'catalogMix' => [
                'labels' => $catalogMixLabels,
                'series' => $catalogMixSeries,
            ],
        ];

        if ($dashboardPermissions['users']) {
            $dashboardCharts['trend']['series'][] = [
                'name' => 'New Users',
                'data' => $trendKeys->map(fn ($key) => (int) ($usersByMonth[$key] ?? 0))->values()->all(),
            ];
        }

        if ($dashboardPermissions['reviews']) {
            $dashboardCharts['trend']['series'][] = [
                'name' => 'New Reviews',
                'data' => $trendKeys->map(fn ($key) => (int) ($reviewsByMonth[$key] ?? 0))->values()->all(),
            ];
        }

        if ($dashboardPermissions['products']) {
            $dashboardCharts['trend']['series'][] = [
                'name' => 'Cart Qty',
                'data' => $trendKeys->map(fn ($key) => (int) ($cartQtyByMonth[$key] ?? 0))->values()->all(),
            ];
        }

        if (!$dashboardPermissions['categories'] && !$dashboardPermissions['products']) {
            $dashboardCharts['categories'] = ['labels' => [], 'series' => []];
        }

        if (!$dashboardPermissions['reviews']) {
            $dashboardCharts['reviewHealth'] = ['labels' => [], 'series' => []];
        }

        if (!$dashboardPermissions['products'] && !$dashboardPermissions['brands']) {
            $dashboardCharts['catalogMix'] = ['labels' => [], 'series' => []];
        }

        if ($dashboardPermissions['brands']) {
            $brandProductStats = DB::table('products')
                ->selectRaw("
                    brand_id,
                    COUNT(*) as total_products,
                    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_products,
                    SUM(CASE WHEN is_featured = 'Yes' THEN 1 ELSE 0 END) as featured_products,
                    ROUND(AVG(final_price), 0) as avg_final_price
                ")
                ->whereNotNull('brand_id')
                ->groupBy('brand_id');

            $brandCartStats = DB::table('products')
                ->join('carts', 'products.id', '=', 'carts.product_id')
                ->selectRaw("
                    products.brand_id,
                    SUM(carts.product_qty) as cart_qty,
                    COUNT(*) as cart_events,
                    MAX(carts.created_at) as last_cart_at
                ")
                ->whereNotNull('products.brand_id')
                ->groupBy('products.brand_id');

            $vendorPerformance = DB::table('brands as brands')
                ->leftJoinSub($brandProductStats, 'product_stats', function ($join) {
                    $join->on('brands.id', '=', 'product_stats.brand_id');
                })
                ->leftJoinSub($brandCartStats, 'cart_stats', function ($join) {
                    $join->on('brands.id', '=', 'cart_stats.brand_id');
                })
                ->selectRaw("
                    brands.id,
                    brands.name,
                    COALESCE(product_stats.total_products, 0) as total_products,
                    COALESCE(product_stats.active_products, 0) as active_products,
                    COALESCE(product_stats.featured_products, 0) as featured_products,
                    COALESCE(product_stats.avg_final_price, 0) as avg_final_price,
                    COALESCE(cart_stats.cart_qty, 0) as cart_qty,
                    COALESCE(cart_stats.cart_events, 0) as cart_events,
                    cart_stats.last_cart_at
                ")
                ->orderByDesc('cart_qty')
                ->orderByDesc('total_products')
                ->limit(8)
                ->get();
        } else {
            $vendorPerformance = collect();
        }

        if ($dashboardPermissions['products']) {
            $cartDemandProducts = DB::table('carts as carts')
                ->join('products as products', 'carts.product_id', '=', 'products.id')
                ->leftJoin('brands as brands', 'products.brand_id', '=', 'brands.id')
                ->selectRaw("
                    products.id,
                    products.product_name,
                    COALESCE(brands.name, 'Unbranded') as brand_name,
                    SUM(carts.product_qty) as cart_qty,
                    COUNT(*) as cart_events,
                    MAX(carts.created_at) as last_added_at
                ")
                ->groupBy('products.id', 'products.product_name', 'brands.name')
                ->orderByDesc('cart_qty')
                ->limit(8)
                ->get();

            $lowStockProducts = Product::with(['category:id,name', 'brand:id,name'])
                ->select('id', 'category_id', 'brand_id', 'product_name', 'stock', 'status', 'final_price', 'updated_at')
                ->orderBy('stock')
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get();
        } else {
            $cartDemandProducts = collect();
            $lowStockProducts = collect();
        }

        $latestReviews = $dashboardPermissions['reviews']
            ? Review::with(['product:id,product_name', 'user:id,name,email'])
                ->select('id', 'product_id', 'user_id', 'rating', 'review', 'status', 'created_at')
                ->latest()
                ->limit(8)
                ->get()
            : collect();

        $recentUsers = $dashboardPermissions['users']
            ? User::select('id', 'name', 'email', 'status', 'created_at')
                ->latest()
                ->limit(8)
                ->get()
            : collect();

        $ordersModuleAvailable = false;

        if ($dashboardPermissions['reviews']) {
            $focusCard = [
                'eyebrow' => 'Priority Queue',
                'value' => $pendingReviewsCount,
                'description' => $pendingReviewsCount === 1
                    ? 'review needs moderation right now.'
                    : 'reviews need moderation right now.',
                'action_label' => 'Open review desk',
                'url' => url('admin/reviews'),
            ];
        } elseif ($dashboardPermissions['products']) {
            $focusCard = [
                'eyebrow' => 'Inventory Risk',
                'value' => $outOfStockProductsCount,
                'description' => $outOfStockProductsCount === 1
                    ? 'product is currently out of stock.'
                    : 'products are currently out of stock.',
                'action_label' => 'Inspect inventory',
                'url' => url('admin/products'),
            ];
        } elseif ($dashboardPermissions['users']) {
            $focusCard = [
                'eyebrow' => 'Customer Base',
                'value' => $usersCount,
                'description' => $usersCount === 1
                    ? 'customer account is available in your workspace.'
                    : 'customer accounts are available in your workspace.',
                'action_label' => 'Open customer desk',
                'url' => url('admin/users'),
            ];
        } elseif ($dashboardPermissions['brands']) {
            $focusCard = [
                'eyebrow' => 'Vendor Layer',
                'value' => $brandsCount,
                'description' => $brandsCount === 1
                    ? 'brand is visible in your current scope.'
                    : 'brands are visible in your current scope.',
                'action_label' => 'View brands',
                'url' => url('admin/brands'),
            ];
        } elseif ($dashboardPermissions['banners']) {
            $focusCard = [
                'eyebrow' => 'Campaign Surface',
                'value' => $bannersCount,
                'description' => $bannersCount === 1
                    ? 'banner is active in your workspace.'
                    : 'banners are active in your workspace.',
                'action_label' => 'Open banners',
                'url' => url('admin/banners'),
            ];
        } elseif ($dashboardPermissions['filters']) {
            $focusCard = [
                'eyebrow' => 'Discovery Layer',
                'value' => $filtersCount,
                'description' => $filtersCount === 1
                    ? 'filter is available for product discovery.'
                    : 'filters are available for product discovery.',
                'action_label' => 'Open filters',
                'url' => url('admin/filters'),
            ];
        } else {
            $focusCard = [
                'eyebrow' => 'Workspace Scope',
                'value' => $accessibleModulesCount,
                'description' => $accessibleModulesCount === 1
                    ? 'module is assigned to this subadmin role.'
                    : 'modules are assigned to this subadmin role.',
                'action_label' => 'Update profile',
                'url' => url('admin/update-details'),
            ];
        }

        return view('admin.dashboard')->with(compact(
            'admin',
            'isAdmin',
            'dashboardPermissions',
            'categoriesCount',
            'productsCount',
            'brandsCount',
            'usersCount',
            'reviewsCount',
            'walletEntriesCount',
            'pendingReviewsCount',
            'bannersCount',
            'filtersCount',
            'subadminsCount',
            'accessibleModulesCount',
            'dashboardCharts',
            'vendorPerformance',
            'cartDemandProducts',
            'lowStockProducts',
            'latestReviews',
            'recentUsers',
            'ordersModuleAvailable',
            'focusCard'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.login');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LoginRequest $request)
    {
        $data = $request->all();
        $loginStatus = $this->adminService->login($data);

        if ($loginStatus == "success"){
            return redirect()->route('dashboard.index');
        } elseif ($loginStatus == "inactive") {
            return redirect()->back()->with('error_message', 'Your account is inactive. Please contact the administrator.');
        } else {
            return redirect()->back()->with('error_message', 'Invalid Email or password');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        $admin = Auth::guard('admin')->user();
        Session::put('admin', $admin);
        return view('admin.update_password');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $data = $request->all();
        $result = $this->adminService->updatePassword($data);

        if ($result['status']) {
            return redirect()->back()->with('success_message', $result['message']);
        } else {
            return redirect()->back()->with('error_message', $result['message']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        Auth::guard('admin')->logout();
        return redirect()->route('admin.login');
    }

    public function verifyPassword(Request $request)
    {
        $data = $request->all();
        return $this->adminService->verifyPassword($data);
    }

    public function updatePasswordRequest(PasswordRequest $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->input();
            $passwordStatus = $this->adminService->updatePassword($data);
            if ($passwordStatus['status'] == "success") {
                return redirect()->back()->with('success_message', $passwordStatus['message']);
            } else {
                return redirect()->back()->with('error_message', $passwordStatus['message']);
            }
        }
    }

    public function editDetails()
    {
        Session::put('page', 'update-details');
        return view('admin.update_details');
    }

    public function updateDetails(DetailRequest $request)
    {
        Session::put('page', 'update-details');
        if ($request->isMethod('post')) {
            $this->adminService->updateDetails($request);
            return redirect()->back()->with('success_message', 'Admin Details updated successfully');
        }
    }

    public function deleteProfileImage(Request $request)
    {
        $status = $this->adminService->deleteProfileImage($request->adminId);
        return response()->json($status);
    }

    public function subadmins()
    {
        Session::put('page', 'subadmins');
        $subadmins = $this->adminService->subadmins();
        return view('admin.subadmins.subadmins')->with(compact('subadmins'));
    }

    public function updateSubadminStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            $status = $this->adminService->updateSubadminStatus($data);
            return response()->json(['status' => $status, 'subadmin_id' => $data['subadmin_id']]);
        }
    }

    public function deleteSubadmin($id)
    {
        $result = $this->adminService->deleteSubadmin($id);
        return redirect()->back()->with('success_message', $result['message']);
    }

    public function addEditSubadmin($id = null)
    {
        if ($id == "") {
            $title = "Add Subadmin";
            $subadmindata = array();
        } else {
            $title = "Edit Subadmin";
            $subadmindata = Admin::find($id);
        }
        return view('admin.subadmins.add_edit_subadmin')->with(compact('title', 'subadmindata'));
    }

    public function addEditSubadminRequest(SubadminRequest $request)
    {
        if ($request->isMethod('post')) {
            $result = $this->adminService->addEditSubadmin($request);
            return redirect('admin/subadmins')->with('success_message', $result['message']);
        }
    }

    public function updateRole($id)
    {
        Session::put('page', 'subadmins');
        $subadminRoles = AdminsRole::where('subadmin_id', $id)->get()->toArray();
        $subadminDetails = Admin::where('id', $id)->first()->toArray();
        $modules = ['categories', 'products', 'brands', 'users', 'reviews', 'wallets', 'filters', 'filters_values', 'banners'];
        $title = "Update ".$subadminDetails['name']." Subadmin Roles/Permissions";
        return view('admin.subadmins.update_roles')->with(compact('title', 'id', 'subadminRoles', 'modules'));
    }

    public function updateRoleRequest(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $service = new AdminService();
            $result = $service->updateRole($request);
            return redirect()->back()->with('success_message', $result['message']);
        }
    }

    public function saveColumnOrder(Request $request)
    {
        $userId = Auth::guard('admin')->id();
        $tableName = $request->table_key;
        if (!$tableName) {
            return response()->json(['status' => 'error', 'message' => 'Table key is required'],400);
        }
        ColumnPreference::updateOrCreate(
            ['admin_id' => $userId, 'table_name' => $tableName],
            ['column_order' => json_encode($request->column_order)]
        );
        return response()->json(['status' => 'success', 'message' => 'Column order saved successfully']);
    }

    public function saveColumnVisibility(Request $request)
    {
        $userId = Auth::guard('admin')->id();
        $tableName = $request->table_key;
        if (!$tableName) {
            return response()->json(['status' => 'error', 'message' => 'Table key is required'],400);
        }
        ColumnPreference::updateOrCreate(
            ['admin_id' => $userId, 'table_name' => $tableName],
            [
                'column_order' => json_encode($request->column_order),
                'hidden_columns' => json_encode($request->hidden_columns)
            ]
        );
        return response()->json(['status' => 'success', 'message' => 'Column visibility saved successfully']);
    }

}
