<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\InventoryController;
use App\Models\Inventory;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('members', MemberController::class);
    Route::apiResource('inventories', InventoryController::class);
    Route::get('analytics', [InventoryController::class, 'analytics']);
    Route::post('/members/import', [MemberController::class, 'import']);
    Route::post('/inventories/import', [InventoryController::class, 'import']);
});
