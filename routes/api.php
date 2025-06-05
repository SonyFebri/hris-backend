<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CheckClock\CheckClockController;
use App\Http\Controllers\Api\CheckClock\CheckClockSettingController;
use App\Http\Controllers\Api\CheckClock\CheckClockSettingTimeController;


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

// Auth (Public)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Admin-only routes
    Route::middleware('is_admin:true')->group(function () {
        // tambahkan rute khusus admin di sini
        Route::prefix('check-clocks')->controller(CheckClockController::class)->group(function () {
            Route::get('/', 'index');            // GET /api/check-clocks
            Route::post('/', 'store');           // POST /api/check-clocks
            Route::get('/{id}', 'show');         // GET /api/check-clocks/{id}
            Route::delete('/{id}', 'destroy');   // DELETE /api/check-clocks/{id}
        });
    });

    // Employee-only routes
    Route::middleware('is_admin:false')->group(function () {

    });
});