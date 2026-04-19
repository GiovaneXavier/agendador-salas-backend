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
        private readonly int    $capacity,
        private readonly array  $resources,
    ) {}

    public static function reconstitute(
        string $id,
        string $name,
        string $colorBg,
        string $colorAccent,
        int    $capacity  = 0,
        array  $resources = [],
    ): self {
        return new self(
            id:          RoomId::fromString($id),
            name:        $name,
            colorBg:     $colorBg,
            colorAccent: $colorAccent,
            capacity:    $capacity,
            resources:   $resources,
        );
    }

    public function id(): RoomId { return $this->id; }
    public function name(): string { return $this->name; }
    public function colorBg(): string { return $this->colorBg; }
    public function colorAccent(): string { return $this->colorAccent; }
    public function capacity(): int { return $this->capacity; }

    /** @return string[] */
    public function resources(): array { return $this->resources; }
}
