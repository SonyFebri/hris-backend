<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CheckClock\CheckClockController;
use App\Http\Controllers\Api\CheckClock\CheckClockSettingController;
use App\Http\Controllers\Api\CheckClock\CheckClockSettingTimeController;
use App\Http\Controllers\Api\Letters\LetterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/send-email', 'sendResetLinkEmail');
    Route::post('/reset-password', 'resetPassword');
});

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::get('/me', 'getUser');
    });
    // Admin-only routes
    Route::middleware('role:true')->group(function () {
        // tambahkan rute khusus admin di sini
        Route::prefix('check-clocks')->controller(CheckClockController::class)->group(function () {
            Route::get('/', 'index');            // GET /api/check-clocks
            Route::post('/approval', 'respondApproval');         // GET /api/check-clocks/show{id}
            Route::delete('/delete{id}', 'destroy');   // DELETE /api/check-clocks/delete{id}
        });
        Route::prefix('letters')->controller(LetterController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/approval', 'updateStatus');
        });
    });

    // Employee-only routes
    Route::middleware('role:false')->group(function () {
    });
});