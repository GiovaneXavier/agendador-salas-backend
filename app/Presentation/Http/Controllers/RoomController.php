<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\Room\UseCases\ListRoomsUseCase;
use App\Presentation\Http\Resources\RoomResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class RoomController extends Controller
{
    public function __construct(
        private readonly ListRoomsUseCase $listRoomsUseCase,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $rooms = $this->listRoomsUseCase->execute();

        return RoomResource::collection($rooms);
    }
}
