<?php

declare(strict_types=1);

namespace App\Application\Booking\DTOs;

use App\Domain\Booking\Entities\Booking;

final class BookingOutputDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $roomId,
        public readonly string $date,
        public readonly int    $startMinute,
        public readonly int    $durationMinutes,
        public readonly int    $endMinute,
        public readonly string $startTime,
        public readonly string $endTime,
        public readonly string $username,
        public readonly string $createdAt,
    ) {}

    public static function fromEntity(Booking $booking): self
    {
        return new self(
            id:              $booking->id()->value(),
            roomId:          $booking->roomId()->value(),
            date:            $booking->period()->date(),
            startMinute:     $booking->period()->startMinute(),
            durationMinutes: $booking->period()->durationMinutes(),
            endMinute:       $booking->period()->endMinute(),
            startTime:       $booking->period()->formatStartTime(),
            endTime:         $booking->period()->formatEndTime(),
            username:        $booking->username(),
            createdAt:       $booking->createdAt()->format('Y-m-d\TH:i:s\Z'),
        );
    }
}
