<?php

declare(strict_types=1);

namespace App\Application\Booking\DTOs;

final class SuggestBookingInputDTO
{
    public function __construct(
        public readonly string $date,
        public readonly int    $durationMinutes,
        public readonly ?int   $preferredStart = null,
    ) {}
}
