<?php

declare(strict_types=1);

namespace App\Application\Booking\DTOs;

final class ListBookingsInputDTO
{
    public function __construct(
        public readonly string $roomId,
        public readonly string $date,
    ) {}
}
