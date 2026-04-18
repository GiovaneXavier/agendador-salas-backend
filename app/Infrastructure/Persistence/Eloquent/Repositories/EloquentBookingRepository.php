<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Booking\Entities\Booking;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Booking\ValueObjects\BookingId;
use App\Domain\Room\ValueObjects\RoomId;
use App\Infrastructure\Persistence\Eloquent\Models\BookingModel;

final class EloquentBookingRepository implements BookingRepositoryInterface
{
    public function save(Booking $booking): void
    {
        BookingModel::updateOrCreate(
            ['id' => $booking->id()->value()],
            [
                'room_id'          => $booking->roomId()->value(),
                'date'             => $booking->period()->date(),
                'start_minute'     => $booking->period()->startMinute(),
                'duration_minutes' => $booking->period()->durationMinutes(),
                'username'         => $booking->username(),
            ]
        );
    }

    public function delete(BookingId $id): void
    {
        BookingModel::destroy($id->value());
    }

    public function findById(BookingId $id): ?Booking
    {
        $model = BookingModel::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    /** @return Booking[] */
    public function findByRoomAndDate(RoomId $roomId, string $date): array
    {
        return BookingModel::where('room_id', $roomId->value())
            ->where('date', $date)
            ->orderBy('start_minute')
            ->get()
            ->map(fn (BookingModel $m) => $this->toDomain($m))
            ->all();
    }

    /** @return Booking[] */
    public function findByRoomAndDateExcluding(RoomId $roomId, string $date, BookingId $excludeId): array
    {
        return BookingModel::where('room_id', $roomId->value())
            ->where('date', $date)
            ->where('id', '!=', $excludeId->value())
            ->orderBy('start_minute')
            ->get()
            ->map(fn (BookingModel $m) => $this->toDomain($m))
            ->all();
    }

    private function toDomain(BookingModel $model): Booking
    {
        return Booking::reconstitute(
            id:              $model->id,
            roomId:          $model->room_id,
            date:            $model->date,
            startMinute:     $model->start_minute,
            durationMinutes: $model->duration_minutes,
            username:        $model->username,
            createdAt:       $model->created_at->toDateTimeString(),
        );
    }
}
