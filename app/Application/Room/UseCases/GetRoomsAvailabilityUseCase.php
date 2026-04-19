<?php

declare(strict_types=1);

namespace App\Application\Room\UseCases;

use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Booking\ValueObjects\BookingPeriod;
use App\Domain\Room\Repositories\RoomRepositoryInterface;

final class GetRoomsAvailabilityUseCase
{
    public function __construct(
        private readonly RoomRepositoryInterface    $roomRepository,
        private readonly BookingRepositoryInterface $bookingRepository,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function execute(string $date): array
    {
        $rooms  = $this->roomRepository->findAll();
        $result = [];

        foreach ($rooms as $room) {
            $bookings   = $this->bookingRepository->findByRoomAndDate($room->id(), $date);
            $freeSlots  = $this->calculateFreeSlots($bookings);
            $nextStart  = count($freeSlots) > 0 ? $freeSlots[0]['start_minute'] : null;

            $result[] = [
                'room_id'              => $room->id()->value(),
                'room_name'            => $room->name(),
                'color_bg'             => $room->colorBg(),
                'color_accent'         => $room->colorAccent(),
                'capacity'             => $room->capacity(),
                'resources'            => $room->resources(),
                'free_slots'           => $freeSlots,
                'next_available_start' => $nextStart,
                'next_available_time'  => $nextStart !== null ? $this->minutesToTime($nextStart) : null,
            ];
        }

        return $result;
    }

    /** @param \App\Domain\Booking\Entities\Booking[] $bookings */
    private function calculateFreeSlots(array $bookings): array
    {
        $dayStart = BookingPeriod::DAY_START;
        $dayEnd   = BookingPeriod::DAY_END;

        usort($bookings, fn ($a, $b) => $a->period()->startMinute() <=> $b->period()->startMinute());

        $freeSlots = [];
        $cursor    = $dayStart;

        foreach ($bookings as $booking) {
            $bookingStart = $booking->period()->startMinute();

            if ($cursor < $bookingStart) {
                $freeSlots[] = [
                    'start_minute'     => $cursor,
                    'end_minute'       => $bookingStart,
                    'start_time'       => $this->minutesToTime($cursor),
                    'end_time'         => $this->minutesToTime($bookingStart),
                    'duration_minutes' => $bookingStart - $cursor,
                ];
            }

            $cursor = max($cursor, $booking->period()->endMinute());
        }

        if ($cursor < $dayEnd) {
            $freeSlots[] = [
                'start_minute'     => $cursor,
                'end_minute'       => $dayEnd,
                'start_time'       => $this->minutesToTime($cursor),
                'end_time'         => $this->minutesToTime($dayEnd),
                'duration_minutes' => $dayEnd - $cursor,
            ];
        }

        return $freeSlots;
    }

    private function minutesToTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
