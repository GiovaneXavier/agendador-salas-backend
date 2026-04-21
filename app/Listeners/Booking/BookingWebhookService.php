<?php

declare(strict_types=1);

namespace App\Listeners\Booking;

use App\Domain\Booking\Entities\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class BookingWebhookService
{
    private string $statusApiUrl;

    private string $secret;

    public function __construct()
    {
        $this->statusApiUrl = rtrim(config('services.room_status.url', 'http://localhost:9000'), '/');
        $this->secret = config('services.room_status.secret', '');
    }

    public function send(string $event, Booking $booking): void
    {
        $period = $booking->period();

        $startTime = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            $period->date() . ' ' . $period->formatStartTime()
        )->format('Y-m-d\TH:i:s');

        $endTime = \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            $period->date() . ' ' . $period->formatEndTime()
        )->format('Y-m-d\TH:i:s');

        try {
            $http = Http::timeout(3);
            if ($this->secret !== '') {
                $http = $http->withToken($this->secret);
            }
            $http->post("{$this->statusApiUrl}/webhooks/booking", [
                'event'      => $event,
                'booking_id' => (string) $booking->id(),
                'room_id'    => (string) $booking->roomId(),
                'username'   => $booking->username(),
                'start_time' => $startTime,
                'end_time'   => $endTime,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Room Status webhook falhou', [
                'event' => $event,
                'booking_id' => (string) $booking->id(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
