<?php

declare(strict_types=1);

namespace App\Domain\Booking\Repositories;

use App\Domain\Booking\Entities\Booking;
use App\Domain\Booking\ValueObjects\BookingId;
use App\Domain\Room\ValueObjects\RoomId;

interface BookingRepositoryInterface
{
    public function save(Booking $booking): void;

    public function delete(BookingId $id): void;

    public function findById(BookingId $id): ?Booking;

    /**
     * Retorna todas as reservas de uma sala em uma data específica.
     * @return Booking[]
     */
    public function findByRoomAndDate(RoomId $roomId, string $date): array;

    /**
     * Retorna todas as reservas de uma sala em uma data, exceto a reserva com o id informado.
     * Útil para verificar conflito ao estender.
     * @return Booking[]
     */
    public function findByRoomAndDateExcluding(RoomId $roomId, string $date, BookingId $excludeId): array;
}
