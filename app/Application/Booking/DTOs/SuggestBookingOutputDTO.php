<?php

declare(strict_types=1);

namespace App\Application\Booking\DTOs;

final class SuggestBookingOutputDTO
{
    public readonly string $startTime;
    public readonly string $endTime;

    public function __construct(
        public readonly string $roomId,
        public readonly string $roomName,
        public readonly string $date,
        public readonly int    $startMinute,
        public readonly int    $endMinute,
        public readonly int    $durationMinutes,
    ) {
        $this->startTime = sprintf('%02d:%02d', intdiv($startMinute, 60), $startMinute % 60);
        $this->endTime   = sprintf('%02d:%02d', intdiv($endMinute, 60), $endMinute % 60);
    }
}
