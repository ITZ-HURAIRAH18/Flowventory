<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\BranchController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});



Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('branches', BranchController::class);
});

use App\Http\Controllers\UserController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users/managers', [UserController::class, 'getManagers']);
});

use App\Http\Controllers\Api\ProductController;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class);
});