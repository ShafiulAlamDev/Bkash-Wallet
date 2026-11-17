<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BkashAgreementController;
use App\Http\Controllers\BkashPaymentController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Auth (Public) ──────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// ── bKash Callbacks (Public — called by bKash servers) ────────────────────────
Route::prefix('bkash')->group(function () {
    Route::get('agreement/callback', [BkashAgreementController::class, 'callback']);
    Route::get('payment/callback',   [BkashPaymentController::class, 'callback']);
});

// ── Authenticated Routes ───────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout',        [AuthController::class, 'logout']);
        Route::get('me',             [AuthController::class, 'me']);
        Route::patch('locale',       [AuthController::class, 'updateLocale']);
    });

    // bKash Agreement (Module 01)
    Route::prefix('bkash/agreement')->group(function () {
        Route::post('create',  [BkashAgreementController::class, 'create']);
        Route::get('status',   [BkashAgreementController::class, 'status']);
    });

    // bKash Payment (Module 02)
    Route::prefix('bkash/payment')->group(function () {
        Route::post('create', [BkashPaymentController::class, 'create']);
    });

    // Transactions & Refunds (Modules 03 & 04)
    Route::prefix('transactions')->group(function () {
        Route::get('/',                       [TransactionController::class, 'index']);
        Route::get('statement/download',      [TransactionController::class, 'downloadStatement']);
        Route::post('{transaction}/refund',   [RefundController::class, 'refund']);
    });
});
