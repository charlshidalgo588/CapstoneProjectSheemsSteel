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
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\POSController;
use App\Http\Middleware\UpdateLastActive;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureRole;

/*
|--------------------------------------------------------------------------
| ROLE GROUPS
|--------------------------------------------------------------------------
| Administrator  -> everything
| Cashier        -> Home, POS, Transactions, Customers
| Inventory Staff-> Home, Products, Categories, Suppliers, Inventory Logs
| Reports + Team -> Administrator ONLY
| Keep in sync with src/utils/permissions.js on the frontend.
*/
$adminOnly       = EnsureRole::class . ':Administrator';
$salesAccess     = EnsureRole::class . ':Administrator,Cashier';
$inventoryAccess = EnsureRole::class . ':Administrator,Inventory Staff';
$anyRole         = EnsureRole::class . ':Administrator,Cashier,Inventory Staff';

/*
|--------------------------------------------------------------------------
| PUBLIC (NO LOGIN) — only what the login page truly needs
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 attempts per 1 minute, per IP

// Best Sellers (login page, public) — safe shape, no cost price
Route::get('/public/best-sellers', [SaleController::class, 'bestSellers']);

// Login-page shop catalog (read-only, no login needed).
// publicList() never exposes cost prices, barcodes or exact stock.
Route::get('/public/product-list', [ProductController::class, 'publicList']);

/*
|--------------------------------------------------------------------------
| EVERYTHING BELOW REQUIRES LOGIN
| EnsureUserIsActive rejects disabled accounts; UpdateLastActive tags the
| "last active" timestamp.
|
| NOTE: the closure must `use` the role variables, otherwise PHP throws
| "Undefined variable $anyRole" and every API route returns 500.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', EnsureUserIsActive::class, UpdateLastActive::class])
    ->group(function () use ($adminOnly, $salesAccess, $inventoryAccess, $anyRole) {

    /* ─── ANY SIGNED-IN USER ─────────────────────────────── */

    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/test', fn () => response()->json('Laravel API Connected Successfully!'));
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard JSON for the Home page (every role lands here)
    Route::get('/dashboard', [HomeController::class, 'dashboard']);

    // Profile / settings (own account)
    Route::put('/user', [AuthController::class, 'update']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);
    Route::put('/user/set-initial-password', [AuthController::class, 'setInitialPassword']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    /* ─── SHARED READS (POS needs them, Inventory needs them) ─── */
    Route::middleware($anyRole)->group(function () {
        Route::get('/product-list', [ProductController::class, 'productList']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);

        Route::get('/products', [ProductController::class, 'index']);
        // NOTE: barcode route must stay above /products/{id}
        Route::get('/products/barcode/{barcode}', [ProductController::class, 'findByBarcode']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::get('/products/{id}/stock', [ProductController::class, 'getStock']);
    });

    /* ─── SALES: Administrator + Cashier ─────────────────── */
    Route::middleware($salesAccess)->group(function () {

        // POS checkout (records the real clerk)
        Route::post('/sales/process', [SaleController::class, 'processSale']);

        // Delete receipts — needs an ADMIN password.
        Route::post('/sales/bulk-delete', [SaleController::class, 'bulkDelete'])
            ->middleware('throttle:10,1'); // slows admin-password guessing

        Route::get('/pos/remnants', [POSController::class, 'remnants']);

        // Transactions list + single receipt
        Route::get('/sales', [SaleController::class, 'apiList']);
        Route::get('/sales/{id}', [SaleController::class, 'apiShow']);

        // Customers (repeat buyer tracking). /customers/list before /customers/{id}
        Route::get('/customers',         [CustomerController::class, 'index']);
        Route::get('/customers/list',    [CustomerController::class, 'list']);
        Route::post('/customers',        [CustomerController::class, 'store']);
        Route::get('/customers/{id}',    [CustomerController::class, 'show']);
        Route::put('/customers/{id}',    [CustomerController::class, 'update']);
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
    });

    /* ─── INVENTORY: Administrator + Inventory Staff ─────── */
    Route::middleware($inventoryAccess)->group(function () {

        Route::get('/generate-sku/{name}', [ProductController::class, 'generateSku']);

        // Category writes
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::post('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        // Product writes
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::post('/products/{id}', [ProductController::class, 'update']); // Vue PUT fallback
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::post('/products/{id}/restock', [ProductController::class, 'restock']);

        // Product detail tabs
        Route::get('/products/{id}/transactions', [TransactionController::class, 'apiProductTransactions']);
        Route::get('/products/{id}/summary', [TransactionController::class, 'apiProductSummary']);
        Route::get('/products/{id}/history', [TransactionController::class, 'apiProductHistory']);

        // Suppliers
        Route::get('/suppliers', [SupplierController::class, 'apiIndex']);
        Route::get('/suppliers/{id}', [SupplierController::class, 'apiShow']);
        Route::post('/suppliers', [SupplierController::class, 'apiStore']);
        Route::put('/suppliers/{id}', [SupplierController::class, 'apiUpdate']);
        Route::patch('/suppliers/{id}', [SupplierController::class, 'apiUpdate']);
        Route::delete('/suppliers/{id}', [SupplierController::class, 'apiDestroy']);

        // Inventory logs + leftover (cut) pieces
        Route::get('/inventory/logs', [InventoryLogController::class, 'index']);
        Route::get('/inventory/remnants', [InventoryLogController::class, 'remnants']);
        Route::patch('/inventory/remnants/{id}', [InventoryLogController::class, 'updateRemnant']);
    });

    /* ─── ADMINISTRATOR ONLY ─────────────────────────────── */
    Route::middleware($adminOnly)->group(function () {

        // Reports
        Route::get('/reports', [ReportsController::class, 'index']);

        // Team / user management
        Route::get   ('/users',                       [UserController::class, 'index']);
        Route::post  ('/users',                       [UserController::class, 'store']);
        Route::patch ('/users/{user}',                [UserController::class, 'update']);
        Route::post  ('/users/{user}/reset-password', [UserController::class, 'resetPassword']);
    });
});