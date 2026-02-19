<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReportController;

// =============================
// Public routes (no auth needed)
// =============================
Route::post('/login', [AuthController::class, 'login']);

// =============================
// Authenticated routes
// =============================
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // =============================
    // Super Admin ONLY routes
    // =============================
    Route::middleware('role:super_admin')->group(function () {

        // Branch management
        Route::apiResource('branches', BranchController::class);

        // Product management
        Route::apiResource('products', ProductController::class);

        // User management
        Route::get('/users/managers', [UserController::class, 'getManagers']);
    });

    // =============================
    // Inventory routes
    // Super Admin = all branches
    // Branch Manager = own branch only (handled in controller)
    // =============================
    Route::middleware('role:super_admin,branch_manager')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index']);
        Route::get('/inventory/history', [InventoryController::class, 'history']);
        Route::get('/inventory/branch/{branchId}/products', [InventoryController::class, 'productsByBranch']);
        Route::post('/inventory/add', [InventoryController::class, 'add']);
        Route::post('/inventory/adjust', [InventoryController::class, 'adjust']);
        Route::post('/inventory/transfer', [InventoryController::class, 'transfer']);
    });

    // =============================
    // Order routes
    // Sales User creates orders
    // Super Admin can also create orders
    // =============================
    Route::middleware('role:super_admin,sales_user')->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);
    });

    // =============================
    // Report routes
    // Super Admin = all branches
    // Branch Manager = own branch only (handled in controller)
    // =============================
    Route::middleware('role:super_admin,branch_manager')->group(function () {
        Route::get('/branches/{branch}/report', [ReportController::class, 'branchReport']);
    });
});