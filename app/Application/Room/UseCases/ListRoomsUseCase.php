<?php

declare(strict_types=1);

namespace App\Application\Room\UseCases;

use App\Application\Room\DTOs\RoomOutputDTO;
use App\Domain\Room\Repositories\RoomRepositoryInterface;

final class ListRoomsUseCase
{
    public function __construct(
        private readonly RoomRepositoryInterface $roomRepository,
    ) {}

    /** @return RoomOutputDTO[] */
    public function execute(): array
    {
        $rooms = $this->roomRepository->findAll();

        return array_map(
            fn ($room) => RoomOutputDTO::fromEntity($room),
            $rooms,
        );
    }
}
