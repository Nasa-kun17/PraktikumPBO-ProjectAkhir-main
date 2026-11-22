<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('accounts', AccountController::class)->only(['index', 'store', 'show']);
        Route::get('accounts/{account}/transactions', [TransactionController::class, 'index']);
        Route::post('accounts/{account}/transactions', [TransactionController::class, 'store']);
    });
});

