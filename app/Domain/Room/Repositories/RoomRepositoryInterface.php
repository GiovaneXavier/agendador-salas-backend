<?php

declare(strict_types=1);

namespace App\Domain\Room\Repositories;

use App\Domain\Room\Entities\Room;
use App\Domain\Room\ValueObjects\RoomId;

interface RoomRepositoryInterface
{
    /** @return Room[] */
    public function findAll(): array;

    public function findById(RoomId $id): ?Room;
}
