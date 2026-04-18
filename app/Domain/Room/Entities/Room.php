<?php

declare(strict_types=1);

namespace App\Domain\Room\Entities;

use App\Domain\Room\ValueObjects\RoomId;

final class Room
{
    public function __construct(
        private readonly RoomId $id,
        private readonly string $name,
        private readonly string $colorBg,
        private readonly string $colorAccent,
    ) {}

    public static function reconstitute(
        string $id,
        string $name,
        string $colorBg,
        string $colorAccent,
    ): self {
        return new self(
            id:          RoomId::fromString($id),
            name:        $name,
            colorBg:     $colorBg,
            colorAccent: $colorAccent,
        );
    }

    public function id(): RoomId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function colorBg(): string
    {
        return $this->colorBg;
    }

    public function colorAccent(): string
    {
        return $this->colorAccent;
    }
}
