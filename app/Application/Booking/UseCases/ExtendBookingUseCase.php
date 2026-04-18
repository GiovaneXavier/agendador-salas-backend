<?php

declare(strict_types=1);

namespace App\Application\Booking\UseCases;

use App\Application\Booking\DTOs\BookingOutputDTO;
use App\Domain\Booking\Exceptions\BookingNotFoundException;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Booking\Services\BookingConflictService;
use App\Domain\Booking\ValueObjects\BookingId;

final class ExtendBookingUseCase
{
    private const EXTEND_MINUTES = 30;

    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepository,
        private readonly BookingConflictService     $conflictService,
    ) {}

    public function execute(string $bookingId): BookingOutputDTO
    {
        $id      = BookingId::fromString($bookingId);
        $booking = $this->bookingRepository->findById($id);

        if ($booking === null) {
            throw new BookingNotFoundException("Reserva '{$bookingId}' não encontrada.");
        }

        // Calcula o período resultante da extensão para verificar conflito
        $extendedPeriod = $booking->period()->extendBy(self::EXTEND_MINUTES);

        $existing = $this->bookingRepository->findByRoomAndDateExcluding(
            $booking->roomId(),
            $booking->period()->date(),
            $id,
        );

        $this->conflictService->assertNoConflict($extendedPeriod, $existing);

        $booking->extend(self::EXTEND_MINUTES);
        $this->bookingRepository->save($booking);

        foreach ($booking->pullDomainEvents() as $event) {
            event($event);
        }

        return BookingOutputDTO::fromEntity($booking);
    }
}
