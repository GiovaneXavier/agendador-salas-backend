<?php

declare(strict_types=1);

namespace App\Listeners\Booking;

use App\Domain\Booking\Events\BookingCancelled;

final class SendBookingCancelledWebhook
{
    public function __construct(private readonly BookingWebhookService $webhook) {}

    public function handle(BookingCancelled $event): void
    {
        $this->webhook->send('cancelled', $event->booking);
    }
}
