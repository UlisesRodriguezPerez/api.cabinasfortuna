<?php
// api-v1.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReservationController;

Route::group(['middleware' => 'jwt.auth'], function () {
    Route::post('/reservations', [ReservationController::class, 'store']);
});

Route::group(['prefix' => 'auth', 'middleware' => ['api']], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('jwt.auth');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('jwt.auth');
    Route::get('me', [AuthController::class, 'me'])->middleware('jwt.auth');
});
