<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Domain\Booking\Exceptions\BookingNotFoundException;
use App\Domain\Booking\Exceptions\NoAvailableSlotException;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Domain\Room\Exceptions\RoomNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (SlotUnavailableException $e): JsonResponse {
            return response()->json([
                'error'   => 'slot_unavailable',
                'message' => $e->getMessage(),
            ], 409);
        });

        $this->renderable(function (BookingNotFoundException $e): JsonResponse {
            return response()->json([
                'error'   => 'booking_not_found',
                'message' => $e->getMessage(),
            ], 404);
        });

        $this->renderable(function (RoomNotFoundException $e): JsonResponse {
            return response()->json([
                'error'   => 'room_not_found',
                'message' => $e->getMessage(),
            ], 404);
        });

        $this->renderable(function (NoAvailableSlotException $e): JsonResponse {
            return response()->json([
                'error'   => 'no_available_slot',
                'message' => $e->getMessage(),
            ], 404);
        });

        $this->renderable(function (ValidationException $e): JsonResponse {
            return response()->json([
                'error'   => 'validation_error',
                'message' => 'Os dados enviados são inválidos.',
                'errors'  => $e->errors(),
            ], 422);
        });
    }
}
