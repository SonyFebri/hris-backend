<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Letters\LetterController;

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
Route::get('/', function () {
    return 'Laravel is working!';
});

Route::middleware('web')->prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'registerAdmin');
    Route::post('/login-admin', 'loginAdmin');
    Route::post('/login-employee', 'loginEmployee');
    Route::post('/logout', 'logout');

});
Route::middleware('is_admin:true')->group(function () {
    // tambahkan rute khusus admin di sini
    Route::prefix('letters')->controller(LetterController::class)->group(function () {
        Route::get('/', 'index');
        Route::post('/approval', 'updateStatus');
    });
});