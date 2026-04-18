<?php

declare(strict_types=1);

namespace App\Domain\Booking\Events;

use App\Domain\Booking\Entities\Booking;

final class BookingExtended
{
    public function __construct(
        public readonly Booking $booking,
        public readonly int     $extraMinutes,
    ) {}
}
