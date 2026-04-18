<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Room\Entities\Room;
use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Domain\Room\ValueObjects\RoomId;
use App\Infrastructure\Persistence\Eloquent\Models\RoomModel;

final class EloquentRoomRepository implements RoomRepositoryInterface
{
    /** @return Room[] */
    public function findAll(): array
    {
        return RoomModel::all()
            ->map(fn (RoomModel $m) => $this->toDomain($m))
            ->all();
    }

    public function findById(RoomId $id): ?Room
    {
        $model = RoomModel::find($id->value());

        return $model ? $this->toDomain($model) : null;
    }

    private function toDomain(RoomModel $model): Room
    {
        return Room::reconstitute(
            id:          $model->id,
            name:        $model->name,
            colorBg:     $model->color_bg,
            colorAccent: $model->color_accent,
        );
    }
}
