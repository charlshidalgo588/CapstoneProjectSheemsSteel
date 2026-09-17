<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\InventoryLogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\UpdateLastActive;
use App\Http\Middleware\EnsureUserIsActive;

/*
|--------------------------------------------------------------------------
| PUBLIC API ROUTES (NO AUTH REQUIRED)
|--------------------------------------------------------------------------
*/

// 🟦 Dashboard JSON for Vue Home Page
Route::get('/dashboard', [HomeController::class, 'dashboard']);

// 🟦 Vue Product List (POS / Inventory)
Route::get('/product-list', [ProductController::class, 'productList']);

// 🟦 Category Public JSON
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// 🟦 Supplier Public JSON
Route::get('/suppliers', [SupplierController::class, 'apiIndex']);
Route::get('/suppliers/{id}', [SupplierController::class, 'apiShow']);

// 🟦 Product Public JSON
Route::get('/products/barcode/{barcode}', [ProductController::class, 'findByBarcode']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/{id}/stock', [ProductController::class, 'getStock']);

// 🟦 Auto SKU Generator
Route::get('/generate-sku/{name}', [ProductController::class, 'generateSku']);

// 🟦 POS Sale Processing
Route::post('/sales/process', [SaleController::class, 'processSale']);

// 🟦 Sales List (Read-only)
Route::get('/sales', [SaleController::class, 'apiList']);

// 🟦 View Single Sale
Route::get('/sales/{id}', [SaleController::class, 'apiShow']);

// 🟦 Delete Sales (Admin validation inside controller)
Route::post('/sales/bulk-delete', [SaleController::class, 'bulkDelete']);

// 🟦 Reports JSON
Route::get('/reports', [ReportsController::class, 'index']);

// 🟦 Inventory Logs JSON
Route::get('/inventory/logs', [InventoryLogController::class, 'index']);

/*
|--------------------------------------------------------------------------
| AUTHENTICATED USER (SANCTUM)
|--------------------------------------------------------------------------
*/

// 🔐 Get authenticated user (used by Layout, Settings, Profile restore)
// EnsureUserIsActive runs first so a disabled user gets rejected/logged
// out before anything else happens; UpdateLastActive tags along after
// it — this is the single most frequent authenticated hit (fired by
// Layout.vue on every page load), so it's the fastest place a disabled
// user gets caught, and the best signal for "last active" of anything
// in this file.
Route::middleware(['auth:sanctum', EnsureUserIsActive::class, UpdateLastActive::class])->get('/user', [AuthController::class, 'user']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (REQUIRE LOGIN)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', EnsureUserIsActive::class, UpdateLastActive::class])->group(function () {

    // 🧪 API Health Check
    Route::get('/test', fn () => response()->json('Laravel API Connected Successfully!'));

    /*
    |--------------------------------------------------------------------------
    | USER SETTINGS / PROFILE
    |--------------------------------------------------------------------------
    */

    // ✅ Update name & email
    Route::put('/user', [AuthController::class, 'update']);

    // ✅ Update password
    Route::put('/user/password', [AuthController::class, 'updatePassword']);

    Route::put('/user/set-initial-password', [AuthController::class, 'setInitialPassword']);

    /*
    |--------------------------------------------------------------------------
    | CATEGORY CRUD
    |--------------------------------------------------------------------------
    */
    Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);

    Route::post('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | PRODUCT CRUD
    |--------------------------------------------------------------------------
    */
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);

    // Vue PUT fallback
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::post('/products/{id}', [ProductController::class, 'update']);

    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // 🔄 Product Restock
    Route::post('/products/{id}/restock', [ProductController::class, 'restock']);

    /*
    |--------------------------------------------------------------------------
    | PRODUCT TRANSACTIONS (VUE)
    |--------------------------------------------------------------------------
    */

    // ✅ Product Transactions Table
    Route::get('/products/{id}/transactions', [TransactionController::class, 'apiProductTransactions']);

    // ✅ Product Summary Cards
    Route::get('/products/{id}/summary', [TransactionController::class, 'apiProductSummary']);

    // ✅ Product History Tab
    Route::get('/products/{id}/history', [TransactionController::class, 'apiProductHistory']);

    /*
    |--------------------------------------------------------------------------
    | SUPPLIER CRUD
    |--------------------------------------------------------------------------
    */
    Route::post('/suppliers', [SupplierController::class, 'apiStore']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'apiUpdate']);
    Route::patch('/suppliers/{id}', [SupplierController::class, 'apiUpdate']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'apiDestroy']);

    /*
    |--------------------------------------------------------------------------
    | NOTIFICATIONS
    |--------------------------------------------------------------------------
    */
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    

        /*
    |--------------------------------------------------------------------------
    | TEAM / USER MANAGEMENT
    |--------------------------------------------------------------------------
    */
    Route::get   ('/users',                       [UserController::class, 'index']);
    Route::post  ('/users',                       [UserController::class, 'store']);
    Route::patch ('/users/{user}',                [UserController::class, 'update']);
    Route::post  ('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
});