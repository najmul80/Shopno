<?php

use Illuminate\Support\Facades\Route;
// Frontend Controllers
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\ProfileController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\SaleController;
use App\Http\Controllers\Frontend\CustomerController;
use App\Http\Controllers\Frontend\StoreStaffController;
use App\Http\Controllers\Frontend\ReportController;
use App\Http\Controllers\Frontend\StoreSettingController;
// Frontend Admin Controllers
use App\Http\Controllers\Frontend\Admin\StoreController as AdminStoreController;
use App\Http\Controllers\Frontend\Admin\UserController as AdminUserController;
use App\Http\Controllers\Frontend\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Frontend\Admin\ActivityLogController as AdminActivityLogController;
use App\Http\Controllers\Frontend\Admin\SettingController as AdminSettingController;


Route::get('/login', function () {
    return view('auth.login');
})->name('login');
Route::get('/register', function () {
    return view('auth.register');
})->name('register.form');
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

Route::get('/', function () {
    return redirect()->route('login');
});

// --- Authenticated Routes ---


// Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard');
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

// Admin Section
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('stores', AdminStoreController::class)->only(['index', 'create', 'edit']);
    Route::resource('users', AdminUserController::class)->only(['index', 'create', 'edit']);
    // Add other admin routes...
});
Route::get('/users', function () { return view('users.index'); })->name('users.index');
Route::get('/admin/roles', function () { return view('admin.roles.index'); })->name('admin.roles.index');
// General Store Section
Route::resource('categories', CategoryController::class)->except(['show', 'store', 'update', 'destroy']);
Route::resource('products', ProductController::class)->except(['show', 'store', 'update', 'destroy']);
Route::get('products/import', [ProductController::class, 'importForm'])->name('products.import.form');
Route::resource('customers', CustomerController::class)->except(['show', 'store', 'update', 'destroy']);

// In routes/web.php
Route::get('/pos', function () { return view('sales.pos'); })->name('pos.index');
Route::get('/sales-history', function () { return view('sales.history'); })->name('sales.history');
Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
// Route::get('sales/pos', [SaleController::class, 'create'])->name('sales.create');
Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');

Route::resource('store/staff', StoreStaffController::class, ['names' => 'store.users'])->except(['show', 'store', 'update', 'destroy']);

// Reports & Settings
Route::get('/reports', function () { return view('reports.index'); })->name('reports.index');
Route::get('/reports/sales-history', function () { return view('reports.sales_history'); })->name('reports.sales_history');

// Route::get('reports/sales-history', [ReportController::class, 'salesHistory'])->name('reports.sales.history.page');
Route::get('my-store/settings', [StoreSettingController::class, 'index'])->name('store.settings.index');
