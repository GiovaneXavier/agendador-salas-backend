<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Booking\Entities\Booking;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Booking\ValueObjects\BookingId;
use App\Domain\Room\ValueObjects\RoomId;

final class InMemoryBookingRepository implements BookingRepositoryInterface
{
    /** @var array<string, Booking> */
    private array $bookings = [];

    public function __construct()
    {
        $today = now()->toDateString();
        $now   = now()->toIso8601String();

        $seed = [
            // Sala Carvalho — 08:00–09:00
            ['b0000001-0001-0001-0001-000000000001', 'a1b2c3d4-0001-0001-0001-000000000001', $today, 480,  60,  'm.silva',     $now],
            // Sala Carvalho — 09:30–11:00
            ['b0000001-0002-0002-0002-000000000002', 'a1b2c3d4-0001-0001-0001-000000000001', $today, 570,  90,  'time.produto',$now],
            // Sala Carvalho — 14:00–15:30
            ['b0000001-0003-0003-0003-000000000003', 'a1b2c3d4-0001-0001-0001-000000000001', $today, 840,  90,  'c.mendes',    $now],
            // Sala Ipê — 07:30–08:30
            ['b0000002-0001-0001-0001-000000000001', 'a1b2c3d4-0002-0002-0002-000000000002', $today, 450,  60,  'eng.team',    $now],
            // Sala Ipê — 10:00–11:30
            ['b0000002-0002-0002-0002-000000000002', 'a1b2c3d4-0002-0002-0002-000000000002', $today, 600,  90,  'l.rocha',     $now],
            // Sala Ipê — 15:00–16:00
            ['b0000002-0003-0003-0003-000000000003', 'a1b2c3d4-0002-0002-0002-000000000002', $today, 900,  60,  'a.beatriz',   $now],
        ];

        foreach ($seed as [$id, $roomId, $date, $start, $duration, $user, $createdAt]) {
            $booking = Booking::reconstitute(
                id:              $id,
                roomId:          $roomId,
                date:            $date,
                startMinute:     $start,
                durationMinutes: $duration,
                username:        $user,
                createdAt:       $createdAt,
            );
            $this->bookings[$id] = $booking;
        }
    }

    public function save(Booking $booking): void
    {
        $this->bookings[$booking->id()->value()] = $booking;
    }

    public function delete(BookingId $id): void
    {
        unset($this->bookings[$id->value()]);
    }

    public function findById(BookingId $id): ?Booking
    {
        return $this->bookings[$id->value()] ?? null;
    }

    public function findByRoomAndDate(RoomId $roomId, string $date): array
    {
        return array_values(array_filter(
            $this->bookings,
            fn (Booking $b) => $b->roomId()->value() === $roomId->value()
                            && $b->period()->date() === $date,
        ));
    }

    public function findByRoomAndDateExcluding(RoomId $roomId, string $date, BookingId $excludeId): array
    {
        return array_values(array_filter(
            $this->bookings,
            fn (Booking $b) => $b->roomId()->value() === $roomId->value()
                            && $b->period()->date() === $date
                            && $b->id()->value() !== $excludeId->value(),
        ));
    }
}
