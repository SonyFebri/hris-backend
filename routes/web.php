<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/sanctum/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show']);

Route::middleware('web')->prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'registerAdmin');
    Route::post('/login-admin', 'loginAdmin');
    Route::post('/login-employee', 'loginEmployee');
    Route::post('/logout', 'logout');
});