<?php

declare(strict_types=1);

namespace App\Application\Booking\UseCases;

use App\Application\Booking\DTOs\BookingOutputDTO;
use App\Application\Booking\DTOs\CreateBookingInputDTO;
use App\Domain\Booking\Entities\Booking;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Booking\Services\BookingConflictService;
use App\Domain\Booking\ValueObjects\BookingId;
use App\Domain\Booking\ValueObjects\BookingPeriod;
use App\Domain\Room\Exceptions\RoomNotFoundException;
use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Domain\Room\ValueObjects\RoomId;

final class CreateBookingUseCase
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly RoomRepositoryInterface    $roomRepository,
        private readonly BookingConflictService     $conflictService,
    ) {}

    public function execute(CreateBookingInputDTO $input): BookingOutputDTO
    {
        $roomId = RoomId::fromString($input->roomId);

        if ($this->roomRepository->findById($roomId) === null) {
            throw new RoomNotFoundException("Sala '{$input->roomId}' não encontrada.");
        }

        $period = BookingPeriod::create(
            $input->date,
            $input->startMinute,
            $input->durationMinutes,
        );

        // Verifica conflitos com reservas existentes na mesma sala e data
        $existing = $this->bookingRepository->findByRoomAndDate($roomId, $input->date);
        $this->conflictService->assertNoConflict($period, $existing);

        $booking = Booking::create(
            id:       BookingId::generate(),
            roomId:   $roomId,
            period:   $period,
            username: $input->username,
        );

        $this->bookingRepository->save($booking);

        foreach ($booking->pullDomainEvents() as $event) {
            event($event);
        }

        return BookingOutputDTO::fromEntity($booking);
    }
}
