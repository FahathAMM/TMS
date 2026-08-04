<?php

use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerMeasurementController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\GarmentPriceController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MeasurementTypeController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\StoreSettingController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TailorController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ─── Public ───────────────────────────────────────────────────────────────────

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// ─── Protected ────────────────────────────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    // Auth self-service
    Route::prefix('auth')->group(function () {
        Route::post('logout',         [AuthController::class, 'logout']);
        Route::get('me',              [AuthController::class, 'me']);
        Route::put('profile',         [AuthController::class, 'updateProfile']);
        Route::put('change-password', [AuthController::class, 'changePassword']);
    });

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Settings read — all authenticated users (sidebar branding)
    Route::get('settings', [StoreSettingController::class, 'index']);

    // Menus — navigation (all authenticated users)
    Route::get('menus/navigation', [MenuController::class, 'navigation']);
    Route::get('menus/flat',       [MenuController::class, 'flat']);

    // Admin-only
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class)->except(['show']);
        Route::get('permissions', [PermissionController::class, 'index']);

        // Menu management
        Route::get('menus',           [MenuController::class, 'index']);
        Route::post('menus',          [MenuController::class, 'store']);
        Route::put('menus/{menu}',    [MenuController::class, 'update']);
        Route::delete('menus/{menu}', [MenuController::class, 'destroy']);

        // Settings (write)
        Route::put('settings/{group}',  [StoreSettingController::class, 'update']);
        Route::post('settings/media',   [StoreSettingController::class, 'uploadMedia']);
        Route::post('settings/upload',  [StoreSettingController::class, 'uploadFile']);

        // Accounting — books are system-generated, admin read access only
        Route::get('accounting/accounts',              [AccountingController::class, 'accounts']);
        Route::get('accounting/journal-entries',        [AccountingController::class, 'journalEntries']);
        Route::get('accounting/reports/trial-balance',  [AccountingController::class, 'trialBalance']);
        Route::get('accounting/reports/profit-loss',    [AccountingController::class, 'profitLoss']);
    });

    // ─── Customers ─────────────────────────────────────────────────────────────

    Route::get('customers',            [CustomerController::class, 'index']);
    Route::get('customers/{customer}', [CustomerController::class, 'show']);
    Route::middleware('permission:create customers')->post('customers',              [CustomerController::class, 'store']);
    Route::middleware('permission:edit customers')->put('customers/{customer}',      [CustomerController::class, 'update']);
    Route::middleware('permission:delete customers')->delete('customers/{customer}', [CustomerController::class, 'destroy']);

    // Customer measurements
    Route::middleware('permission:view customers')->get('customers/{customer}/measurements', [CustomerMeasurementController::class, 'index']);
    Route::middleware('permission:edit customers')->put('customers/{customer}/measurements',  [CustomerMeasurementController::class, 'update']);

    // ─── Measurement Types (lookup) ────────────────────────────────────────────

    Route::middleware('permission:view measurement_types')->get('measurement-types', [MeasurementTypeController::class, 'index']);
    Route::middleware('permission:create measurement_types')->post('measurement-types', [MeasurementTypeController::class, 'store']);
    Route::middleware('permission:edit measurement_types')->put('measurement-types/{measurementType}', [MeasurementTypeController::class, 'update']);
    Route::middleware('permission:delete measurement_types')->delete('measurement-types/{measurementType}', [MeasurementTypeController::class, 'destroy']);

    // ─── Tailors ────────────────────────────────────────────────────────────────

    Route::middleware('permission:view tailors')->get('tailors',            [TailorController::class, 'index']);
    Route::middleware('permission:view tailors')->get('tailors/{tailor}',   [TailorController::class, 'show']);
    Route::middleware('permission:create tailors')->post('tailors',                    [TailorController::class, 'store']);
    Route::middleware('permission:edit tailors')->put('tailors/{tailor}',              [TailorController::class, 'update']);
    Route::middleware('permission:delete tailors')->delete('tailors/{tailor}',         [TailorController::class, 'destroy']);

    // ─── Garment Pricing Reference ─────────────────────────────────────────────

    Route::middleware('permission:view garment_prices')->get('garment-prices', [GarmentPriceController::class, 'index']);
    Route::middleware('permission:create garment_prices')->post('garment-prices', [GarmentPriceController::class, 'store']);
    Route::middleware('permission:edit garment_prices')->put('garment-prices/{garmentPrice}', [GarmentPriceController::class, 'update']);
    Route::middleware('permission:delete garment_prices')->delete('garment-prices/{garmentPrice}', [GarmentPriceController::class, 'destroy']);

    // ─── Tailoring Orders ───────────────────────────────────────────────────────

    Route::middleware('permission:view tailoring_orders')->group(function () {
        Route::get('orders',                [OrderController::class, 'index']);
        Route::get('orders/{order}',        [OrderController::class, 'show']);
        Route::get('orders/{order}/payments', [OrderController::class, 'payments']);
    });
    Route::middleware('permission:create tailoring_orders')->post('orders', [OrderController::class, 'store']);
    Route::middleware('permission:edit tailoring_orders')->group(function () {
        Route::post('orders/{order}/complete',                                [OrderController::class, 'complete']);
        Route::post('orders/{order}/payments',                                 [OrderController::class, 'storePayment']);
        Route::post('orders/{order}/items/{item}/advance-status',              [OrderController::class, 'advanceItemStatus']);
        Route::post('orders/{order}/items/{item}/qc',                          [OrderController::class, 'qcItem']);
        Route::post('orders/{order}/items/{item}/assign-tailor',               [OrderController::class, 'assignTailor']);
        Route::post('orders/{order}/notify',                                   [OrderController::class, 'notify']);
    });
    Route::middleware('permission:view tailoring_orders')->get('orders/{order}/notifications', [OrderController::class, 'notifications']);

    // ─── Fabrics & Trims (Products) ────────────────────────────────────────────

    Route::middleware('permission:view products')->get('products',            [ProductController::class, 'index']);
    Route::middleware('permission:view products')->get('products/{product}',  [ProductController::class, 'show']);
    Route::middleware('permission:view products')->get('products/{product}/stock-history', [ProductController::class, 'stockHistory']);
    Route::middleware('permission:create products')->post('products',                   [ProductController::class, 'store']);
    Route::middleware('permission:edit products')->put('products/{product}',            [ProductController::class, 'update']);
    Route::middleware('permission:delete products')->delete('products/{product}',       [ProductController::class, 'destroy']);

    Route::middleware('permission:view categories')->get('categories', [CategoryController::class, 'index']);
    Route::middleware('permission:view categories')->get('categories/{category}', [CategoryController::class, 'show']);
    Route::middleware('permission:create categories')->post('categories', [CategoryController::class, 'store']);
    Route::middleware('permission:edit categories')->put('categories/{category}', [CategoryController::class, 'update']);
    Route::middleware('permission:delete categories')->delete('categories/{category}', [CategoryController::class, 'destroy']);

    Route::middleware('permission:view brands')->get('brands', [BrandController::class, 'index']);
    Route::middleware('permission:view brands')->get('brands/{brand}', [BrandController::class, 'show']);
    Route::middleware('permission:create brands')->post('brands', [BrandController::class, 'store']);
    Route::middleware('permission:edit brands')->put('brands/{brand}', [BrandController::class, 'update']);
    Route::middleware('permission:delete brands')->delete('brands/{brand}', [BrandController::class, 'destroy']);

    // ─── Inventory & Stock Movements ────────────────────────────────────────────

    Route::middleware('permission:view inventory')->group(function () {
        Route::get('inventory',           [InventoryController::class, 'index']);
        Route::get('inventory/history',   [InventoryController::class, 'history']);
        Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);
        Route::get('stock-movements',     [StockMovementController::class, 'index']);
    });
    Route::middleware('permission:adjust inventory')->post('inventory/adjust', [InventoryController::class, 'adjust']);

    // ─── Suppliers & Purchases ──────────────────────────────────────────────────

    Route::middleware('permission:view suppliers')->get('suppliers',              [SupplierController::class, 'index']);
    Route::middleware('permission:view suppliers')->get('suppliers/{supplier}',   [SupplierController::class, 'show']);
    Route::middleware('permission:view suppliers')->get('suppliers/{supplier}/ledger', [SupplierController::class, 'ledger']);
    Route::middleware('permission:create suppliers')->post('suppliers',                     [SupplierController::class, 'store']);
    Route::middleware('permission:edit suppliers')->put('suppliers/{supplier}',             [SupplierController::class, 'update']);
    Route::middleware('permission:delete suppliers')->delete('suppliers/{supplier}',        [SupplierController::class, 'destroy']);

    Route::middleware('permission:view purchases')->get('purchases',              [PurchaseController::class, 'index']);
    Route::middleware('permission:view purchases')->get('purchases/{purchase}',   [PurchaseController::class, 'show']);
    Route::middleware('permission:view purchases')->get('purchases/{purchase}/payments', [PurchaseController::class, 'payments']);
    Route::middleware('permission:create purchases')->post('purchases',                     [PurchaseController::class, 'store']);
    Route::middleware('permission:edit purchases')->put('purchases/{purchase}',             [PurchaseController::class, 'update']);
    Route::middleware('permission:edit purchases')->post('purchases/{purchase}/receive',    [PurchaseController::class, 'receive']);
    Route::middleware('permission:edit purchases')->post('purchases/{purchase}/payments',   [PurchaseController::class, 'storePayment']);
    Route::middleware('permission:delete purchases')->delete('purchases/{purchase}',        [PurchaseController::class, 'destroy']);

    // ─── Expenses ───────────────────────────────────────────────────────────────

    Route::middleware('permission:view expenses')->get('expenses', [ExpenseController::class, 'index']);
    Route::middleware('permission:create expenses')->post('expenses', [ExpenseController::class, 'store']);

    // ─── Operational Reports ────────────────────────────────────────────────────

    Route::middleware('permission:view reports')->prefix('reports')->group(function () {
        Route::get('orders',              [ReportController::class, 'orders']);
        Route::get('payments',            [ReportController::class, 'payments']);
        Route::get('outstanding-balances', [ReportController::class, 'outstandingBalances']);
        Route::get('stock',               [ReportController::class, 'stock']);
        Route::get('purchases',           [ReportController::class, 'purchases']);
        Route::get('expenses',            [ReportController::class, 'expenses']);
        Route::get('tailor-productivity', [ReportController::class, 'tailorProductivity']);
    });
});
