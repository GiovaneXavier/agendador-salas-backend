<?php

declare(strict_types=1);

namespace App\Application\Booking\UseCases;

use App\Application\Booking\DTOs\SuggestBookingInputDTO;
use App\Application\Booking\DTOs\SuggestBookingOutputDTO;
use App\Domain\Booking\Exceptions\NoAvailableSlotException;
use App\Domain\Booking\ValueObjects\BookingPeriod;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Room\Repositories\RoomRepositoryInterface;

final class SuggestBookingUseCase
{
    public function __construct(
        private readonly RoomRepositoryInterface    $roomRepository,
        private readonly BookingRepositoryInterface $bookingRepository,
    ) {}

    public function execute(SuggestBookingInputDTO $input): SuggestBookingOutputDTO
    {
        $rooms          = $this->roomRepository->findAll();
        $preferredStart = $input->preferredStart ?? BookingPeriod::DAY_START;

        foreach ($rooms as $room) {
            $bookings = $this->bookingRepository->findByRoomAndDate($room->id(), $input->date);

            $slot = $this->findFirstAvailableSlot(
                $bookings,
                $input->date,
                $input->durationMinutes,
                $preferredStart,
            );

            if ($slot !== null) {
                return new SuggestBookingOutputDTO(
                    roomId:          $room->id()->value(),
                    roomName:        $room->name(),
                    date:            $input->date,
                    startMinute:     $slot,
                    endMinute:       $slot + $input->durationMinutes,
                    durationMinutes: $input->durationMinutes,
                );
            }
        }

        throw new NoAvailableSlotException(
            "Não há horários disponíveis para {$input->durationMinutes} minutos em {$input->date}."
        );
    }

    /**
     * Percorre os slots de 30 em 30 minutos a partir de $from.
     * Quando encontra conflito, pula direto para o fim da reserva conflitante.
     *
     * @param \App\Domain\Booking\Entities\Booking[] $bookings
     */
    private function findFirstAvailableSlot(
        array  $bookings,
        string $date,
        int    $durationMinutes,
        int    $from,
    ): ?int {
        $candidate = $from;

        while ($candidate + $durationMinutes <= BookingPeriod::DAY_END) {
            $period   = BookingPeriod::create($date, $candidate, $durationMinutes);
            $conflict = null;

            foreach ($bookings as $booking) {
                if ($period->overlaps($booking->period())) {
                    $conflict = $booking->period()->endMinute();
                    break;
                }
            }

            if ($conflict === null) {
                return $candidate;
            }

            $candidate = $conflict;
        }

        return null;
    }
}
