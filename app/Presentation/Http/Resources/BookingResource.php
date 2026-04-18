<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Application\Booking\DTOs\BookingOutputDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BookingOutputDTO */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'room_id'          => $this->roomId,
            'date'             => $this->date,
            'start_minute'     => $this->startMinute,
            'duration_minutes' => $this->durationMinutes,
            'end_minute'       => $this->endMinute,
            'start_time'       => $this->startTime,
            'end_time'         => $this->endTime,
            'username'         => $this->username,
            'created_at'       => $this->createdAt,
        ];
    }
}
