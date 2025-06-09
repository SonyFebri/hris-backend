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
Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'registerAdmin');
    Route::post('/login-admin', 'loginAdmin');
    Route::post('/login-employee', 'loginEmployee');
});

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout');
        Route::get('/me', 'getUser');
    });
    // Admin-only routes
    Route::middleware('is_admin:true')->group(function () {
        // tambahkan rute khusus admin di sini
        Route::prefix('check-clocks')->controller(CheckClockController::class)->group(function () {
            Route::get('/', 'index');            // GET /api/check-clocks
            Route::post('/add', 'store');           // POST /api/check-clocks/add
            Route::get('/show{id}', 'show');         // GET /api/check-clocks/show{id}
            Route::delete('/delete{id}', 'destroy');   // DELETE /api/check-clocks/delete{id}
        });
    });

    // Employee-only routes
    Route::middleware('is_admin:false')->group(function () {

    });
});