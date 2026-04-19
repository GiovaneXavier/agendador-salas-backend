<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Domain\Room\Entities\Room;
use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Domain\Room\ValueObjects\RoomId;

final class InMemoryRoomRepository implements RoomRepositoryInterface
{
    /** @var Room[] */
    private array $rooms;

    public function __construct()
    {
        $this->rooms = [
            Room::reconstitute('a1b2c3d4-0001-0001-0001-000000000001', 'Sala Carvalho', '#eae4dc', '#4a3d2f'),
            Room::reconstitute('a1b2c3d4-0002-0002-0002-000000000002', 'Sala Ipê',      '#e2e6df', '#2f4a3d'),
        ];
    }

    public function findAll(): array
    {
        return $this->rooms;
    }

    public function findById(RoomId $id): ?Room
    {
        foreach ($this->rooms as $room) {
            if ($room->id()->value() === $id->value()) {
                return $room;
            }
        }
        return null;
    }
}
