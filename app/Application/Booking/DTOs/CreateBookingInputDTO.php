<?php

declare(strict_types=1);

namespace App\Application\Booking\DTOs;

final class CreateBookingInputDTO
{
    public function __construct(
        public readonly string $roomId,
        public readonly string $date,
        public readonly int    $startMinute,
        public readonly int    $durationMinutes,
        public readonly string $username,
    ) {}
}
