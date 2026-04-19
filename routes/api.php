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
| Rotas do totem (sem autenticação):
|   GET    /api/rooms
|   GET    /api/bookings?room_id=&date=
|   POST   /api/bookings
|   PATCH  /api/bookings/{id}/extend
|   DELETE /api/bookings/{id}
|
| Rotas para agentes IA (requerem X-Api-Key):
|   GET    /api/rooms/availability?date=
|   POST   /api/bookings/suggest
|
*/

Route::middleware('throttle:120,1')->group(function () {

    // --- Totem (uso público interno) ---
    Route::prefix('rooms')->group(function () {
        Route::get('/', [RoomController::class, 'index']);
    });

    Route::prefix('bookings')->group(function () {
        Route::get('/',              [BookingController::class, 'index']);
        Route::post('/',             [BookingController::class, 'store']);
        Route::patch('/{id}/extend', [BookingController::class, 'extend']);
        Route::delete('/{id}',       [BookingController::class, 'destroy']);
    });

    // --- Agentes IA (requerem X-Api-Key no header) ---
    Route::middleware('api.key')->group(function () {
        Route::get('/rooms/availability',  [RoomController::class,  'availability']);
        Route::post('/bookings/suggest',   [BookingController::class, 'suggest']);
    });

});
