<?php

use Illuminate\Support\Facades\Route;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Response;

// Admin Controllers
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\FilterController;
use App\Http\Controllers\Admin\FilterValueController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\WalletController as AdminWalletController;

// Front Controllers
use App\Http\Controllers\Front\IndexController;
use App\Http\Controllers\Front\ProductController as ProductFrontController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CouponController as CouponFrontController;
use App\Http\Controllers\Front\AuthController;
use App\Http\Controllers\Front\AccountController;
use App\Http\Controllers\Front\ReviewController as ReviewFrontController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\OrderController as OrderFrontController;


// Models
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;

/*Route::get('/', function () {
    return view('welcome');
});*/

Route::get('product-image/{size}/{filename}', function ($size, $filename) {
    $sizes = config('images_sizes.product');
    if (!isset($sizes[$size])) {
        abort(404, 'Invalid size');
    }
    $width = $sizes[$size]['width'];
    $height = $sizes[$size]['height'];
    $path = public_path('front/images/products/' . $filename);
    if (!file_exists($path)) {
        abort(404, 'Image not found');
    }
    $manager = new ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
    $image = $manager->read($path)->resize($width, $height, function ($constraint) {
        $constraint->aspectRatio();
        $constraint->upsize();
    });
    $binary = $image->toJpeg(85); // Compression with 85% quality
    return Response::make($binary)->header('Content-Type', 'image/jpeg');

});


Route::prefix('admin')->group(function () {
    Route::get('/', function () {
        return auth()->guard('admin')->check()
            ? redirect()->route('dashboard.index')
            : redirect()->route('admin.login');
    })->name('admin.home');

    // Show Login Form
    Route::get('login', [AdminController::class, 'create'])->name('admin.login');
    // Handle Login Form Submission
    Route::post('login', [AdminController::class, 'store'])->name('admin.login.request');
    Route::group(['middleware' => 'admin'], function () {

        // Dashboard Route
        Route::resource('dashboard', AdminController::class)->only(['index']);

        // Display Update Password Page Route
        Route::get('update-password', [AdminController::class, 'edit'])->name('admin.update-password');
        // Verify Password Route
        Route::post('verify-password', [AdminController::class, 'verifyPassword'])->name('admin.verify-password');
        // Update Password Route
        Route::post('admin/update-password', [AdminController::class, 'updatePasswordRequest'])
        ->name('admin.update-password.request');

        // Display Update Details Page Route
        Route::get('update-details', [AdminController::class, 'editDetails'])->name('admin.update-details');
        // Update Admin Details Route
        Route::post('update-details', [AdminController::class, 'updateDetails'])->name('admin.update-details.request');
        // Delete Admin Profile Image Route
        Route::post('delete-profile-image', [AdminController::class, 'deleteProfileImage']);

        // Subadmins Route
        Route::get('subadmins', [AdminController::class, 'subadmins']);
        // Update Subadmin Status Route
        Route::post('update-subadmin-status', [AdminController::class, 'updateSubadminStatus']);
        // Add Edit Subadmin Route
        Route::get('add-edit-subadmin/{id?}', [AdminController::class, 'addEditSubadmin']);
        // Add Edit Subadmin Request Route
        Route::post('add-edit-subadmin/request', [AdminController::class, 'addEditSubadminRequest']);
        // Delete Subadmin Route
        Route::get('delete-subadmin/{id}', [AdminController::class, 'deleteSubadmin']);
        // Update Role Route
        Route::get('/update-role/{id}', [AdminController::class, 'updateRole']);
        // Update Role Request Route
        Route::post('/update-role/request', [AdminController::class, 'updateRoleRequest']);

        // Categories Route
        Route::resource('categories', CategoryController::class);
        // Update Category Status Route
        Route::post('update-category-status', [CategoryController::class, 'updateCategoryStatus']);
        // Delete Category Image Route
        Route::post('delete-category-image', [CategoryController::class, 'deleteCategoryImage']);
        // Delete Size Chart Image Route
        Route::post('delete-sizechart-image', [CategoryController::class, 'deleteSizeChartImage']);

        // Products Route
        Route::resource('products', ProductController::class);
        // Update Product Status Route
        Route::post('update-product-status', [ProductController::class, 'updateProductStatus']);

        // Upload Product Image Route
        Route::post('/product/upload-image', [ProductController::class, 'uploadImage'])->name('product.upload.image');
        // Upload Product Images Route
        Route::post('/product/upload-images', [ProductController::class, 'uploadImages'])->name('product.upload.images');
        // Upload Product Video Route
        Route::post('/product/upload-video', [ProductController::class, 'uploadVideo'])->name('product.upload.video');
        // Delete Product Image Route
        Route::post('/product/delete-temp-image', [ProductController::class, 'deleteTempImage'])->name('product.delete.temp.image');
        // Delete Product Images Route
        Route::get('delete-product-image/{id?}', [ProductController::class, 'deleteProductImage']);
        // Delete Product Main Image Route
        Route::get('delete-product-main-image/{id?}', [ProductController::class, 'deleteProductMainImage']);
        // Delete Product Video Route
        Route::get('delete-product-video/{id}', [ProductController::class, 'deleteProductVideo']);


        // Update Product Image Sorting Route
        Route::post('/products/update-image-sorting', [ProductController::class, 'updateImageSorting'])->name('admin.products.update-image-sorting');
        Route::post('/products/delete-dropzone-image', [ProductController::class, 'deleteDropzoneImage'])->name('admin.products.delete-image');
        Route::post('/products/delete-temp-image', [ProductController::class, 'deleteTempProductImage'])->name('product.delete.temp.altimage');
        Route::post('/products/delete-temp-video', [ProductController::class, 'deleteTempProductVideo'])->name('product.delete.temp.video');

        // Filters CRUD + status update
        Route::resource('filters', FilterController::class);
        Route::post('update-filter-status', [FilterController::class, 'updateFilterStatus'])->name('filters.update-status');

        // Filter Values CRUD (nested inside filters)
        // We map parameter name 'filter-values' => 'value' so request()->route('value') works.
        Route::prefix('filters/{filter}')->group(function () {
            Route::resource('filter-values', FilterValueController::class)->parameters(['filter-values' => 'value']);
        });

        // Attributes
        // Update Product Attribute Status
        Route::post('update-attribute-status', [ProductController::class, 'updateAttributeStatus']);
        // Delete Product Attribute
        Route::get('delete-product-attribute/{id}', [ProductController::class, 'deleteProductAttribute']);

        // Save Column Order Route
        /* Route::post('/save-column-order', [AdminController::class, 'saveColumnOrder']); */
        Route::post('/save-column-visibility', [AdminController::class, 'saveColumnVisibility'])->name('admin.save-column-visibility');

        // Brands Route
        Route::resource('brands', BrandController::class);
        // Update Brand Status Route
        Route::post('update-brand-status', [BrandController::class, 'updateBrandStatus']);
        // Delete Brand Image Route
        Route::post('delete-brand-image', [BrandController::class, 'deleteBrandImage']);

        // Banners Route
        Route::resource('banners', BannerController::class);
        // Update Banner Status Route
        Route::post('update-banner-status', [BannerController::class, 'updateBannerStatus']);

        // Coupons Route
        Route::resource('coupons', CouponController::class);
        // Update Coupon Status Route
        Route::post('update-coupon-status', [CouponController::class, 'updateCouponStatus']);

        // Users
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('update-user-status', [UserController::class, 'updateUserStatus']);

        // Reviews
        Route::resource('reviews', ReviewController::class);
        Route::post('update-review-status', [ReviewController::class, 'updateReviewStatus']);

        // Wallets
        Route::resource('wallets', AdminWalletController::class)->except(['show']);
        Route::get('wallets/live-balance', [AdminWalletController::class, 'liveBalance'])->name('wallets.live-balance');
        Route::post('update-wallet-status', [AdminWalletController::class, 'updateWalletStatus'])->name('wallets.update-status');

        // Orders
        Route::resource('orders', OrderController::class);
        Route::post('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');


        // Admin Logout Route
        Route::get('logout', [AdminController::class, 'destroy'])->name('admin.logout');
    });
});

Route::group([], function () {
    // Static routes first (higher priority)
    Route::get('/search-products', [ProductFrontController::class, 'ajaxSearch'])
        ->name('search.products');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::get('/cart/refresh', [CartController::class, 'refresh'])->name('cart.refresh');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{cart}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{cart}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::post('/cart/apply-wallet', [CartController::class, 'applyWallet'])->name('cart.apply.wallet');
    Route::post('/cart/remove-wallet', [CartController::class, 'removeWallet'])->name('cart.remove.wallet');
    Route::post('/cart/checkout-preview', [CartController::class, 'checkoutPreview'])->name('cart.checkout.preview');
    Route::post('/cart/complete-wallet-checkout', [CartController::class, 'completeWalletCheckout'])->name('cart.complete-wallet-checkout');
    Route::post('/get-product-price', [ProductFrontController::class, 'getProductPrice'])->name('product.price');
    Route::post('/get-product-variant', [ProductFrontController::class, 'getProductVariant'])->name('product.variant');

    // Coupon routes
    Route::post('/cart/apply-coupon', [CouponFrontController::class, 'apply'])->name('cart.apply.coupon');
    Route::post('/cart/remove-coupon', [CouponFrontController::class, 'remove'])->name('cart.remove.coupon');

    // Category listing pages
    if (Schema::hasTable('categories')) {
        try {
            $catUrls = Category::where('status', 1)->pluck('url')->toArray();
            foreach ($catUrls as $url) {
                if (!empty($url)) {
                    Route::get("/$url", [ProductFrontController::class, 'index']);
                }
            }
        } catch (\Throwable $e) {
            // Ignore errors during migration/seed.
        }
    }

    // Product Detail Page
    if(Schema::hasTable('products')) {
        try {
            $productUrls = Product::where('status', 1)->whereNotNull('product_url')->where('product_url', '!=', '')->pluck('product_url')->toArray();
            foreach ($productUrls as $url) {
                if (!empty($url)) {
                    Route::get("/$url", [ProductFrontController::class, 'detail']);
                }
            }
        } catch (\Throwable $e) {
            // Ignore errors during migration/seed
        }
    }

    // Home page (lowest priority, acts as fallback)
    Route::get('/', [IndexController::class, 'index'])->name('home');

    // User Auth pages (login/register) only for guests, and logout / user pages only for authenticated users
    Route::prefix('user')->name('user.')->group(function() {

        // Route only accessible when NOT logged in
        Route::middleware('guest')->group(function() {
            Route::get('login', [AuthController::class, 'showLogin'])->name('login');
            Route::post('login', [AuthController::class, 'login'])->name('login.post');
            Route::get('register', [AuthController::class, 'showRegister'])->name('register');
            Route::post('register', [AuthController::class, 'register'])->name('register.post');

            Route::get('password/forgot', [AuthController::class, 'showForgotForm'])->name('password.forgot');
            Route::post('password/forgot', [AuthController::class, 'sendResetLink'])->name('password.forgot.post');
            Route::post('password/reset', [AuthController::class, 'resetPassword'])->name('password.reset.post');
        });

        Route::middleware('signed')->get(
            'account/email/verify/{user}/{hash}',
            [AccountController::class, 'verifyPendingEmail']
        )->name('account.email.verify');

        // Route only accessible when logged in
        Route::middleware('auth')->group(function() {
            Route::get('account', [AccountController::class, 'edit'])->name('account');
            Route::get('account/location/counties', [AccountController::class, 'counties'])->name('account.locations.counties');
            Route::get('account/location/sub-counties', [AccountController::class, 'subCounties'])->name('account.locations.sub-counties');
            Route::put('account', [AccountController::class, 'update'])->name('account.update');
            Route::get('account/wallet', [AccountController::class, 'wallet'])->name('account.wallet');
            Route::post('account/wallet/top-up', [AccountController::class, 'requestWalletTopUp'])->name('account.wallet.top-up');
            Route::post('account/addresses', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
            Route::put('account/addresses/{address}', [AccountController::class, 'updateAddress'])->name('account.addresses.update');
            Route::delete('account/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('account.addresses.destroy');
            Route::post('account/addresses/{address}/default', [AccountController::class, 'setDefaultAddress'])->name('account.addresses.default');
            Route::put('account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
            Route::post('account/email/resend', [AccountController::class, 'resendPendingEmailVerification'])->name('account.email.resend');
            // Show checkout page
            Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
            Route::post('/checkout/summary', [CheckoutController::class, 'summary'])->name('checkout.summary');
            Route::post('/checkout/wallet/apply', [CheckoutController::class, 'applyWallet'])->name('checkout.wallet.apply');
            Route::post('/checkout/wallet/remove', [CheckoutController::class, 'removeWallet'])->name('checkout.wallet.remove');
            Route::post('/checkout/addresses/{address}/select', [CheckoutController::class, 'selectAddress'])->name('checkout.addresses.select');
            // Save new delivery address (Ajax or normal POST)
            Route::post('/checkout/add-address', [CheckoutController::class, 'addAddress'])->name('checkout.addresses');
            Route::put('/checkout/addresses/{address}', [CheckoutController::class, 'updateAddress'])->name('checkout.addresses.update');
            Route::delete('/checkout/addresses/{address}', [CheckoutController::class, 'destroyAddress'])->name('checkout.addresses.destroy');
            // place order (form submit)
            Route::post('/checkout/place-order', [CheckoutController::class, 'placeOrder'])->name('checkout.placeOrder');
            Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

            // User Order Listing Route
            Route::get('orders', [OrderFrontController::class, 'index'])->name('orders.index');

            // User Order Detail Route
            Route::get('orders/{order}', [OrderFrontController::class, 'show'])->name('orders.show');


            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    Route::middleware('guest')->get('user/password/reset/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');

    // Product Reviews - accessible only to authenticated users
    Route::middleware('auth')->group(function() {
        Route::post('/product-review', [ReviewFrontController::class, 'store'])->name('product.review.store');

    });
});
