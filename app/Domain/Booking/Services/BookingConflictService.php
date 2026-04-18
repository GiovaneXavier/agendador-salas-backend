<?php

declare(strict_types=1);

namespace App\Domain\Booking\Services;

use App\Domain\Booking\Entities\Booking;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Domain\Booking\ValueObjects\BookingPeriod;

final class BookingConflictService
{
    /**
     * Verifica se o período desejado conflita com reservas existentes.
     * Lança SlotUnavailableException se houver sobreposição.
     *
     * @param Booking[] $existingBookings
     */
    public function assertNoConflict(BookingPeriod $desired, array $existingBookings): void
    {
        foreach ($existingBookings as $existing) {
            if ($desired->overlaps($existing->period())) {
                throw new SlotUnavailableException(sprintf(
                    'Conflito de horário: %s–%s já está reservado por %s.',
                    $existing->period()->formatStartTime(),
                    $existing->period()->formatEndTime(),
                    $existing->username(),
                ));
            }
        }
    }

    /**
     * Verifica sem lançar exceção — útil nos testes unitários.
     *
     * @param Booking[] $existingBookings
     */
    public function hasConflict(BookingPeriod $desired, array $existingBookings): bool
    {
        foreach ($existingBookings as $existing) {
            if ($desired->overlaps($existing->period())) {
                return true;
            }
        }
        return false;
    }
}
