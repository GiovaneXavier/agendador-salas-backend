<?php

declare(strict_types=1);

namespace App\Listeners\Booking;

use App\Domain\Booking\Events\BookingCreated;

final class SendBookingCreatedWebhook
{
    public function __construct(private readonly BookingWebhookService $webhook) {}

    public function handle(BookingCreated $event): void
    {
        $this->webhook->send('created', $event->booking);
    }
}
