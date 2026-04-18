<?php

declare(strict_types=1);

namespace App\Application\Booking\UseCases;

use App\Application\Booking\DTOs\BookingOutputDTO;
use App\Application\Booking\DTOs\ListBookingsInputDTO;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Room\ValueObjects\RoomId;

final class ListBookingsUseCase
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepository,
    ) {}

    /** @return BookingOutputDTO[] */
    public function execute(ListBookingsInputDTO $input): array
    {
        $roomId   = RoomId::fromString($input->roomId);
        $bookings = $this->bookingRepository->findByRoomAndDate($roomId, $input->date);

        return array_map(
            fn ($booking) => BookingOutputDTO::fromEntity($booking),
            $bookings,
        );
    }
}
