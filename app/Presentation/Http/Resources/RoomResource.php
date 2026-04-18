<?php

declare(strict_types=1);

namespace App\Presentation\Http\Resources;

use App\Application\Room\DTOs\RoomOutputDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RoomOutputDTO */
class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'color_bg'     => $this->colorBg,
            'color_accent' => $this->colorAccent,
        ];
    }
}
