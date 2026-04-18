<?php

declare(strict_types=1);

use App\Presentation\Http\Controllers\BookingController;
use App\Presentation\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Agendador de Salas
|--------------------------------------------------------------------------
|
| GET    /api/rooms
| GET    /api/bookings?room_id=&date=
| POST   /api/bookings
| PATCH  /api/bookings/{id}/extend
| DELETE /api/bookings/{id}
|
*/

Route::prefix('rooms')->group(function () {
    Route::get('/', [RoomController::class, 'index']);
});

Route::prefix('bookings')->group(function () {
    Route::get('/',             [BookingController::class, 'index']);
    Route::post('/',            [BookingController::class, 'store']);
    Route::patch('/{id}/extend',[BookingController::class, 'extend']);
    Route::delete('/{id}',      [BookingController::class, 'destroy']);
});
