<?php

declare(strict_types=1);

namespace App\Domain\Booking\ValueObjects;

use InvalidArgumentException;

final class BookingPeriod
{
    public const DAY_START = 420;  // 07:00 em minutos
    public const DAY_END   = 1200; // 20:00 em minutos
    public const VALID_DURATIONS = [30, 60, 90, 120];

    private function __construct(
        private readonly string $date,
        private readonly int    $startMinute,
        private readonly int    $durationMinutes,
    ) {
        $this->validate();
    }

    public static function create(string $date, int $startMinute, int $durationMinutes): self
    {
        return new self($date, $startMinute, $durationMinutes);
    }

    private function validate(): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->date)) {
            throw new InvalidArgumentException("Data inválida: {$this->date}. Use o formato YYYY-MM-DD.");
        }

        if (!in_array($this->durationMinutes, self::VALID_DURATIONS, true)) {
            $valid = implode(', ', self::VALID_DURATIONS);
            throw new InvalidArgumentException("Duração inválida. Valores permitidos: {$valid} minutos.");
        }

        if ($this->startMinute < self::DAY_START) {
            throw new InvalidArgumentException(
                sprintf('Horário de início (%s) é anterior ao início do expediente (07:00).', $this->formatMinutes($this->startMinute))
            );
        }

        $endMinute = $this->startMinute + $this->durationMinutes;
        if ($endMinute > self::DAY_END) {
            throw new InvalidArgumentException(
                sprintf('Horário de fim (%s) ultrapassa o limite do expediente (20:00).', $this->formatMinutes($endMinute))
            );
        }
    }

    public function date(): string
    {
        return $this->date;
    }

    public function startMinute(): int
    {
        return $this->startMinute;
    }

    public function durationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function endMinute(): int
    {
        return $this->startMinute + $this->durationMinutes;
    }

    public function overlaps(self $other): bool
    {
        if ($this->date !== $other->date) {
            return false;
        }

        return $this->startMinute < $other->endMinute()
            && $this->endMinute() > $other->startMinute;
    }

    public function extendBy(int $minutes): self
    {
        return new self($this->date, $this->startMinute, $this->durationMinutes + $minutes);
    }

    public function formatStartTime(): string
    {
        return $this->formatMinutes($this->startMinute);
    }

    public function formatEndTime(): string
    {
        return $this->formatMinutes($this->endMinute());
    }

    private function formatMinutes(int $totalMinutes): string
    {
        $h = intdiv($totalMinutes, 60);
        $m = $totalMinutes % 60;
        return sprintf('%02d:%02d', $h, $m);
    }
}
