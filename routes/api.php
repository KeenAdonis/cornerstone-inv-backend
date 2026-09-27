<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\StockInController;
use App\Http\Controllers\Api\StockAdjustmentController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\InventoryCountController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\DashboardAnalyticsController;
use App\Http\Controllers\Api\ProductRankingController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()->load([
                    'assignedBranches',
                    'assignedWarehouses',
                ]),
            ],
        ]);
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Administration
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Dashboard Analytics
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard/product-demand',
            [DashboardAnalyticsController::class, 'productDemand']
        );

        /*
        |--------------------------------------------------------------------------
        | Product Demand Ranking
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/product-rankings',
            [ProductRankingController::class, 'index']
        );

        /*
        |--------------------------------------------------------------------------
        | Users
        |--------------------------------------------------------------------------
        */

        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::patch(
            '/users/{user}/status',
            [UserController::class, 'toggleStatus']
        );
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Branches
        |--------------------------------------------------------------------------
        */

        Route::get('/branches', [BranchController::class, 'index']);
        Route::post('/branches', [BranchController::class, 'store']);
        Route::put('/branches/{branch}', [BranchController::class, 'update']);
        Route::patch(
            '/branches/{branch}/status',
            [BranchController::class, 'toggleStatus']
        );
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Warehouses - Management
        |--------------------------------------------------------------------------
        */

        Route::post('/warehouses', [WarehouseController::class, 'store']);
        Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update']);
        Route::patch(
            '/warehouses/{warehouse}/status',
            [WarehouseController::class, 'toggleStatus']
        );
        Route::delete('/warehouses/{warehouse}', [WarehouseController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Master Data Read Access
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin,warehouse_coordinator,branch_coordinator'
    )->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Warehouses
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/warehouses',
            [WarehouseController::class, 'index']
        );

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/products',
            [ProductController::class, 'index']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Product Master Data
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin,warehouse_coordinator'
    )->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Categories
        |--------------------------------------------------------------------------
        */

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::patch(
            '/categories/{category}/status',
            [CategoryController::class, 'toggleStatus']
        );
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | Products - Management
        |--------------------------------------------------------------------------
        */

        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::patch(
            '/products/{product}/status',
            [ProductController::class, 'toggleStatus']
        );
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin,warehouse_coordinator,branch_coordinator'
    )->group(function () {
        Route::get(
            '/inventory',
            [InventoryController::class, 'index']
        );

        Route::patch(
            '/inventory/{inventory}/stock-levels',
            [InventoryController::class, 'updateStockLevels']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Stock In
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin,warehouse_coordinator'
    )->group(function () {
        Route::get(
            '/stock-ins',
            [StockInController::class, 'index']
        );

        Route::post(
            '/stock-ins',
            [StockInController::class, 'store']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Stock Adjustment
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin,warehouse_coordinator,branch_coordinator'
    )->group(function () {
        Route::get(
            '/stock-adjustments',
            [StockAdjustmentController::class, 'index']
        );

        Route::post(
            '/stock-adjustments',
            [StockAdjustmentController::class, 'store']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory Count
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:branch_coordinator'
    )->group(function () {
        Route::get(
            '/inventory-counts',
            [InventoryCountController::class, 'index']
        );

        Route::post(
            '/inventory-counts',
            [InventoryCountController::class, 'store']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Purchase Orders
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin,branch_coordinator,warehouse_coordinator'
    )->group(function () {
        Route::get(
            '/purchase-orders',
            [PurchaseOrderController::class, 'index']
        );
    });

    Route::middleware(
        'role:branch_coordinator'
    )->group(function () {
        Route::post(
            '/purchase-orders',
            [PurchaseOrderController::class, 'store']
        );

        Route::patch(
            '/purchase-orders/{purchaseOrder}/deliver',
            [PurchaseOrderController::class, 'deliver']
        );
    });

    Route::middleware(
        'role:admin'
    )->group(function () {
        Route::patch(
            '/purchase-orders/{purchaseOrder}/review',
            [PurchaseOrderController::class, 'review']
        );
    });

    Route::middleware(
        'role:warehouse_coordinator'
    )->group(function () {
        Route::patch(
            '/purchase-orders/{purchaseOrder}/process',
            [PurchaseOrderController::class, 'process']
        );

        Route::patch(
            '/purchase-orders/{purchaseOrder}/release',
            [PurchaseOrderController::class, 'release']
        );

        Route::patch(
            '/purchase-orders/{purchaseOrder}/complete',
            [PurchaseOrderController::class, 'complete']
        );
    });

    Route::middleware(
        'role:admin,warehouse_coordinator,branch_coordinator'
    )->group(function () {
        Route::get(
            '/stock-movements',
            [StockMovementController::class, 'index']
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Activity Logs
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/activity-logs',
        [ActivityLogController::class, 'index']
    );
});