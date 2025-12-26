<?php

use Illuminate\Support\Facades\Route;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Response;

// Admin Controllers
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\BrandController;

// Front Controllers
use App\Http\Controllers\Front\IndexController;


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

        // Logout Route
        Route::get('logout', [AdminController::class, 'destroy'])->name('admin.logout');
    });
});

Route::namespace('App\Http\Controllers\Front')->group(function () {
    Route::get('/', [IndexController::class, 'index']);
});