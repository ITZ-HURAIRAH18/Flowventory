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
    // All branches (for dropdowns like Order Create, Inventory)
    // Any authenticated user can see branch names
    // =============================
    Route::get('/all-branches', function () {
        return \App\Models\Branch::all();
    });

    // =============================
    // My branches (for Reports — own branch only)
    // Admin = all, Manager = own branch
    // =============================
    Route::get('/my-branches', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $user->load('role');

        if ($user->role->name === 'super_admin') {
            return \App\Models\Branch::all();
        }

        // Manager — return branches where they are the manager
        return \App\Models\Branch::where('manager_id', $user->id)->get();
    });

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
    // Read-only product list (for inventory form dropdowns)
    // Admin + Manager can list products, full CRUD stays admin-only
    // =============================
    Route::middleware('role:super_admin,branch_manager')->group(function () {
        Route::get('/all-products', [ProductController::class, 'index']);
    });

    // =============================
    // Inventory routes
    // Super Admin = all branches
    // Branch Manager = own branch only (handled in controller)
    // =============================
    Route::middleware('role:super_admin,branch_manager')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index']);
        Route::get('/inventory/history', [InventoryController::class, 'history']);
        Route::post('/inventory/add', [InventoryController::class, 'add']);
        Route::post('/inventory/adjust', [InventoryController::class, 'adjust']);
        Route::post('/inventory/transfer', [InventoryController::class, 'transfer']);
    });

    // Products by branch — needed by Order Create (sales + manager)
    Route::middleware('role:super_admin,branch_manager,sales_user')->group(function () {
        Route::get('/inventory/branch/{branchId}/products', [InventoryController::class, 'productsByBranch']);
    });

    // =============================
    // Order routes
    // Per assignment spec: Admin = No, Manager = Yes, Sales = Yes
    // =============================
    Route::middleware('role:branch_manager,sales_user')->group(function () {
        Route::post('/orders', [OrderController::class, 'store']);
    });

    // =============================
    // Report routes
    // Super Admin = all branches
    // Branch Manager = own branch only (handled in controller)
    // =============================
    Route::middleware('role:super_admin,branch_manager')->group(function () {
        Route::get('/report/summary', [ReportController::class, 'summaryReport']);
        Route::get('/branches/{branch}/report', [ReportController::class, 'branchReport']);
    });
});