<?php

declare(strict_types=1);

namespace App\Listeners\Booking;

use App\Domain\Booking\Events\BookingExtended;

final class SendBookingExtendedWebhook
{
    public function __construct(private readonly BookingWebhookService $webhook) {}

    public function handle(BookingExtended $event): void
    {
        $this->webhook->send('extended', $event->booking);
    }
}
