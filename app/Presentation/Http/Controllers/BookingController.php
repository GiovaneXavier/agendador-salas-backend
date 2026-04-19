<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\Booking\DTOs\CreateBookingInputDTO;
use App\Application\Booking\DTOs\ListBookingsInputDTO;
use App\Application\Booking\DTOs\SuggestBookingInputDTO;
use App\Application\Booking\UseCases\CancelBookingUseCase;
use App\Application\Booking\UseCases\CreateBookingUseCase;
use App\Application\Booking\UseCases\ExtendBookingUseCase;
use App\Application\Booking\UseCases\ListBookingsUseCase;
use App\Application\Booking\UseCases\SuggestBookingUseCase;
use App\Presentation\Http\Requests\CancelBookingRequest;
use App\Presentation\Http\Requests\CreateBookingRequest;
use App\Presentation\Http\Requests\ListBookingsRequest;
use App\Presentation\Http\Requests\SuggestBookingRequest;
use App\Presentation\Http\Resources\BookingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class BookingController extends Controller
{
    public function __construct(
        private readonly ListBookingsUseCase   $listBookingsUseCase,
        private readonly CreateBookingUseCase  $createBookingUseCase,
        private readonly ExtendBookingUseCase  $extendBookingUseCase,
        private readonly CancelBookingUseCase  $cancelBookingUseCase,
        private readonly SuggestBookingUseCase $suggestBookingUseCase,
    ) {}

    public function index(ListBookingsRequest $request): AnonymousResourceCollection
    {
        $input = new ListBookingsInputDTO(
            roomId: $request->validated('room_id'),
            date:   $request->validated('date'),
        );

        $bookings = $this->listBookingsUseCase->execute($input);

        return BookingResource::collection($bookings);
    }

    public function store(CreateBookingRequest $request): BookingResource
    {
        $input = new CreateBookingInputDTO(
            roomId:          $request->validated('room_id'),
            date:            $request->validated('date'),
            startMinute:     $request->validated('start_minute'),
            durationMinutes: $request->validated('duration_minutes'),
            username:        $request->validated('username'),
        );

        $booking = $this->createBookingUseCase->execute($input);

        return (new BookingResource($booking))->response()->setStatusCode(201);
    }

    public function extend(string $id): BookingResource
    {
        $booking = $this->extendBookingUseCase->execute($id);

        return new BookingResource($booking);
    }

    public function destroy(CancelBookingRequest $request, string $id): JsonResponse
    {
        $this->cancelBookingUseCase->execute($id, $request->validated('username'));

        return response()->json(['message' => 'Reserva cancelada com sucesso.'], 200);
    }

    public function suggest(SuggestBookingRequest $request): JsonResponse
    {
        $input = new SuggestBookingInputDTO(
            date:            $request->validated('date'),
            durationMinutes: $request->validated('duration_minutes'),
            preferredStart:  $request->validated('preferred_start'),
        );

        $suggestion = $this->suggestBookingUseCase->execute($input);

        return response()->json(['data' => [
            'room_id'          => $suggestion->roomId,
            'room_name'        => $suggestion->roomName,
            'date'             => $suggestion->date,
            'start_minute'     => $suggestion->startMinute,
            'end_minute'       => $suggestion->endMinute,
            'start_time'       => $suggestion->startTime,
            'end_time'         => $suggestion->endTime,
            'duration_minutes' => $suggestion->durationMinutes,
        ]]);
    }
}
