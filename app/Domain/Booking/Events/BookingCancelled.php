<?php

declare(strict_types=1);

namespace App\Domain\Booking\Events;

use App\Domain\Booking\Entities\Booking;

final class BookingCancelled
{
    public function __construct(public readonly Booking $booking) {}
}
