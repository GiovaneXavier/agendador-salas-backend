<?php

declare(strict_types=1);

namespace App\Domain\Booking\ValueObjects;

use InvalidArgumentException;

final class BookingId
{
    private function __construct(private readonly string $value)
    {
        if (empty(trim($value))) {
            throw new InvalidArgumentException('BookingId não pode ser vazio.');
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function generate(): self
    {
        // UUID v4 via PHP puro — sem dependência de framework
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);
        return new self(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4)));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
