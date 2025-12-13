<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\CategoryController;
Route::get('/', function () {
    return view('welcome');
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

        // Logout Route
        Route::get('logout', [AdminController::class, 'destroy'])->name('admin.logout');
    });
});