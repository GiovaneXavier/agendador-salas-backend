<?php

declare(strict_types=1);

namespace App\Domain\Booking\Entities;

use App\Domain\Booking\Events\BookingCancelled;
use App\Domain\Booking\Events\BookingCreated;
use App\Domain\Booking\Events\BookingExtended;
use App\Domain\Booking\Exceptions\BookingNotFoundException;
use App\Domain\Booking\ValueObjects\BookingId;
use App\Domain\Booking\ValueObjects\BookingPeriod;
use App\Domain\Room\ValueObjects\RoomId;

final class Booking
{
    /** @var object[] */
    private array $domainEvents = [];

    private function __construct(
        private readonly BookingId $id,
        private readonly RoomId    $roomId,
        private BookingPeriod      $period,
        private readonly string    $username,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        BookingId     $id,
        RoomId        $roomId,
        BookingPeriod $period,
        string        $username,
    ): self {
        $booking = new self(
            id:        $id,
            roomId:    $roomId,
            period:    $period,
            username:  $username,
            createdAt: new \DateTimeImmutable(),
        );

        $booking->recordEvent(new BookingCreated($booking));

        return $booking;
    }

    public static function reconstitute(
        string $id,
        string $roomId,
        string $date,
        int    $startMinute,
        int    $durationMinutes,
        string $username,
        string $createdAt,
    ): self {
        return new self(
            id:        BookingId::fromString($id),
            roomId:    RoomId::fromString($roomId),
            period:    BookingPeriod::create($date, $startMinute, $durationMinutes),
            username:  $username,
            createdAt: new \DateTimeImmutable($createdAt),
        );
    }

    public function extend(int $extraMinutes = 30): void
    {
        $this->period = $this->period->extendBy($extraMinutes);
        $this->recordEvent(new BookingExtended($this, $extraMinutes));
    }

    public function cancel(string $requestingUsername): void
    {
        if (strtolower($requestingUsername) !== strtolower($this->username)) {
            throw new BookingNotFoundException(
                "Usuário '{$requestingUsername}' não é o organizador desta reserva."
            );
        }

        $this->recordEvent(new BookingCancelled($this));
    }

    public function id(): BookingId
    {
        return $this->id;
    }

    public function roomId(): RoomId
    {
        return $this->roomId;
    }

    public function period(): BookingPeriod
    {
        return $this->period;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return object[] */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
