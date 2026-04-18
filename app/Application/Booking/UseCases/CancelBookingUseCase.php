<?php

declare(strict_types=1);

namespace App\Application\Booking\UseCases;

use App\Domain\Booking\Exceptions\BookingNotFoundException;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Booking\ValueObjects\BookingId;

final class CancelBookingUseCase
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookingRepository,
    ) {}

    public function execute(string $bookingId, string $username): void
    {
        $id      = BookingId::fromString($bookingId);
        $booking = $this->bookingRepository->findById($id);

        if ($booking === null) {
            throw new BookingNotFoundException("Reserva '{$bookingId}' não encontrada.");
        }

        // Valida organizador — lança exceção se username não bater
        $booking->cancel($username);

        $this->bookingRepository->delete($id);

        foreach ($booking->pullDomainEvents() as $event) {
            event($event);
        }
    }
}
