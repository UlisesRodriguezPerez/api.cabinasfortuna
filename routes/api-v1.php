<?php
// api-v1.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleCalendarController;
use App\Http\Controllers\Api\ReservationController;
use Carbon\Carbon;

Route::group(['middleware' => 'jwt.auth'], function () {
    Route::post('/reservations', [ReservationController::class, 'store']);
});

Route::group(['prefix' => 'auth', 'middleware' => ['api']], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('jwt.auth');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('jwt.auth');
    Route::get('me', [AuthController::class, 'me'])->middleware('jwt.auth');
});

Route::get('/google/redirect', [GoogleCalendarController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/google/callback', [GoogleCalendarController::class, 'handleGoogleCallback'])->name('google.callback');
Route::post('/google/event/create', [GoogleCalendarController::class, 'createEvent'])->name('google.event.create');

Route::get('/test-event', function () {
    $reservation = new \stdClass();
    $reservation->name = "Test Reservation";
    $reservation->date = Carbon::now()->addDays(1)->format('Y-m-d\TH:i:s');  // Asegúrate de que la fecha sea en el futuro
    $reservation->nights = 2;
    $reservation->cabin = 1;

    $controller = new \App\Http\Controllers\Api\GoogleCalendarController();
    return $controller->createEvent($reservation);
});