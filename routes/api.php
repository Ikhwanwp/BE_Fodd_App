<?php

use App\Http\Controllers\API\FoodController;
use App\Http\Controllers\API\MidtransController;
use App\Http\Controllers\API\TransactionController;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// beberapa api yang diakses oleh user akan masuk ke group
// ini hanya bisa diakses saat login dan memeberikan token saat proses login
Route::middleware('auth:sanctum')->group(function () {
    // mengambil data profil jika sudah login
    Route::get('user', [UserController::class], 'fetch');
    // update profile
    Route::get('user', [UserController::class], 'updateProfile');
    // UpdatePhoto
    Route::get('user/photo', [UserController::class], 'UpdatePhoto');
    // logout
    Route::get('logout', [UserController::class], 'logout');

    Route::post('checkout', [TransactionController::class, 'checkout']);

    route::get('transaction', [TransactionController::class, 'all']);
    route::post('transaction/{id}', [TransactionController::class, 'update']);
});


// untuk login dan register
// Gak perlu login
Route::post('login', [UserController::class], 'login');
Route::post('register', [UserController::class], 'register');

Route::post('food', [FoodController::class, 'all']);

// untuk  mengirim ke midtrans
Route::post('midtrans/callback', [MidtransController::class, 'callback']);
