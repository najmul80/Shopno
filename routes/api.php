<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StoreController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StoreSettingController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::prefix('v1')->group(function () {

    // --- Public Routes (No Authentication Required) ---
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('register', 'register');
        Route::post('login', 'login');
        Route::post('refresh-token', 'refreshToken');
        Route::post('password/forgot', 'forgotPassword');
        Route::post('password/verify-otp', 'verifyOtp');
        Route::post('password/reset', 'resetPassword');
    });


    // --- Protected Routes (Authentication Required) ---
    // All routes inside this group are protected by your custom JWT guard.
    Route::middleware('auth:api')->group(function () {
    
        // Authenticated User Routes
        Route::prefix('auth')->controller(AuthController::class)->group(function () {
            Route::get('me', 'me');
            Route::post('logout', 'logout');
            Route::post('profile/update', 'updateProfile');
        });

        // --- Core API Resources ---
        // These are now explicitly protected.
        Route::apiResource('stores', \App\Http\Controllers\Api\StoreController::class);
        Route::apiResource('categories', \App\Http\Controllers\Api\CategoryController::class);
        Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class);
        Route::apiResource('customers', \App\Http\Controllers\Api\CustomerController::class);
        Route::apiResource('users', \App\Http\Controllers\Api\UserController::class); // General user management
        
        // Sales and Invoices
        Route::get('sales', [\App\Http\Controllers\Api\SalesController::class, 'index']);
        Route::get('sales/{sale}', [\App\Http\Controllers\Api\SalesController::class, 'show']);
        Route::post('sales', [\App\Http\Controllers\Api\SalesController::class, 'store'])->middleware('permission:process sales');
        Route::get('invoices/{sale}/json', [\App\Http\Controllers\Api\InvoiceController::class, 'show']);
        Route::get('invoices/{sale}/pdf', [\App\Http\Controllers\Api\InvoiceController::class, 'downloadPDF'])->middleware('permission:download invoices');

        // Product-specific sub-routes
        Route::prefix('products/{product}')->group(function () {
            // ... (Your specific product routes like images, variants, etc. go here) ...
            Route::post('variants', [\App\Http\Controllers\Api\ProductController::class, 'storeVariant']);
            Route::put('variants/{variant}', [\App\Http\Controllers\Api\ProductController::class, 'updateVariant']);
        });

        // Dashboard
        Route::get('dashboard/summary', [\App\Http\Controllers\Api\DashboardController::class, 'summary'])->middleware('permission:view dashboard_summary');
        Route::get('dashboard/sales-trends', [\App\Http\Controllers\Api\DashboardController::class, 'salesTrends'])->middleware('permission:view dashboard_summary');
        
        // Notifications
        Route::get('notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        // ... other notification routes ...

        // Store Settings
        Route::get('store-settings', [\App\Http\Controllers\Api\StoreSettingController::class, 'index'])->middleware('permission:view own_store_settings');
        Route::put('store-settings', [\App\Http\Controllers\Api\StoreSettingController::class, 'updateMultiple'])->middleware('permission:manage own_store_settings');
        
        // --- Super Admin Only Routes ---
        Route::middleware('role:super-admin')->prefix('admin')->group(function () {
            Route::apiResource('roles', \App\Http\Controllers\Api\Admin\RoleController::class);
            Route::get('settings', [\App\Http\Controllers\Api\Admin\SettingController::class, 'index']);
            Route::put('settings', [\App\Http\Controllers\Api\Admin\SettingController::class, 'updateMultiple']);
        });
    });
});
