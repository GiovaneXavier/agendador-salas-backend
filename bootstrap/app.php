<?php

use App\Domain\Booking\Exceptions\BookingNotFoundException;
use App\Domain\Booking\Exceptions\NoAvailableSlotException;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Domain\Room\Exceptions\RoomNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'api.key' => \App\Http\Middleware\ApiKeyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // 409 — Conflito de horário
        $exceptions->render(function (SlotUnavailableException $e): JsonResponse {
            return response()->json([
                'error'   => 'slot_unavailable',
                'message' => $e->getMessage(),
            ], 409);
        });

        // 404 — Reserva não encontrada ou usuário não autorizado
        $exceptions->render(function (BookingNotFoundException $e): JsonResponse {
            return response()->json([
                'error'   => 'booking_not_found',
                'message' => $e->getMessage(),
            ], 404);
        });

        // 404 — Sala não encontrada
        $exceptions->render(function (RoomNotFoundException $e): JsonResponse {
            return response()->json([
                'error'   => 'room_not_found',
                'message' => $e->getMessage(),
            ], 404);
        });

        // 404 — Nenhum slot disponível para sugestão
        $exceptions->render(function (NoAvailableSlotException $e): JsonResponse {
            return response()->json([
                'error'   => 'no_available_slot',
                'message' => $e->getMessage(),
            ], 404);
        });

        // 422 — Validação de Form Request (padrão Laravel, mas formatado para JSON)
        $exceptions->render(function (ValidationException $e): JsonResponse {
            return response()->json([
                'error'   => 'validation_error',
                'message' => 'Os dados enviados são inválidos.',
                'errors'  => $e->errors(),
            ], 422);
        });

    })->create();
