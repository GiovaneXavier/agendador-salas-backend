<?php

declare(strict_types=1);

namespace App\Presentation\Http\Controllers;

use App\Application\Room\UseCases\GetRoomsAvailabilityUseCase;
use App\Application\Room\UseCases\ListRoomsUseCase;
use App\Presentation\Http\Requests\GetAvailabilityRequest;
use App\Presentation\Http\Resources\RoomResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class RoomController extends Controller
{
    public function __construct(
        private readonly ListRoomsUseCase            $listRoomsUseCase,
        private readonly GetRoomsAvailabilityUseCase $availabilityUseCase,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $rooms = $this->listRoomsUseCase->execute();

        return RoomResource::collection($rooms);
    }

    public function availability(GetAvailabilityRequest $request): JsonResponse
    {
        $data = $this->availabilityUseCase->execute($request->validated('date'));

        return response()->json(['data' => $data]);
    }
}
