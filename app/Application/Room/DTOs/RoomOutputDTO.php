<?php

declare(strict_types=1);

namespace App\Application\Room\DTOs;

use App\Domain\Room\Entities\Room;

final class RoomOutputDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $colorBg,
        public readonly string $colorAccent,
    ) {}

    public static function fromEntity(Room $room): self
    {
        return new self(
            id:          $room->id()->value(),
            name:        $room->name(),
            colorBg:     $room->colorBg(),
            colorAccent: $room->colorAccent(),
        );
    }
}
