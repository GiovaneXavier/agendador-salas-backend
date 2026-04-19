<?php

declare(strict_types=1);

use App\Application\Booking\DTOs\SuggestBookingInputDTO;
use App\Application\Booking\UseCases\SuggestBookingUseCase;
use App\Domain\Booking\Entities\Booking;
use App\Domain\Booking\Exceptions\NoAvailableSlotException;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Booking\ValueObjects\BookingId;
use App\Domain\Booking\ValueObjects\BookingPeriod;
use App\Domain\Room\Entities\Room;
use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Domain\Room\ValueObjects\RoomId;

function suggestRoomRepo(array $rooms): RoomRepositoryInterface
{
    return new class($rooms) implements RoomRepositoryInterface {
        public function __construct(private array $rooms) {}
        public function findAll(): array           { return $this->rooms; }
        public function findById(RoomId $id): ?Room { return null; }
    };
}

function suggestBookingRepo(array $bookings): BookingRepositoryInterface
{
    return new class($bookings) implements BookingRepositoryInterface {
        public function __construct(private array $bookings) {}
        public function save(Booking $b): void {}
        public function delete(BookingId $id): void {}
        public function findById(BookingId $id): ?Booking { return null; }
        public function findByRoomAndDate(RoomId $r, string $d): array { return $this->bookings; }
        public function findByRoomAndDateExcluding(RoomId $r, string $d, BookingId $x): array { return []; }
    };
}

function suggestRoom(string $id = 'a1b2c3d4-0001-0001-0001-000000000001', string $name = 'Sala Carvalho'): Room
{
    return Room::reconstitute($id, $name, '#eae4dc', '#4a3d2f', 8, []);
}

function suggestBooking(int $start, int $duration, string $date = '2099-01-01'): Booking
{
    return Booking::create(
        id:       BookingId::generate(),
        roomId:   RoomId::fromString('a1b2c3d4-0001-0001-0001-000000000001'),
        period:   BookingPeriod::create($date, $start, $duration),
        username: 'test.user',
    );
}

describe('SuggestBookingUseCase', function () {

    beforeEach(function () {
        $this->date = '2099-01-01';
    });

    it('sugere 07:00 quando a sala não tem reservas', function () {
        $useCase = new SuggestBookingUseCase(
            suggestRoomRepo([suggestRoom()]),
            suggestBookingRepo([]),
        );

        $result = $useCase->execute(new SuggestBookingInputDTO($this->date, 60));

        expect($result->startTime)->toBe('07:00')
            ->and($result->endTime)->toBe('08:00')
            ->and($result->durationMinutes)->toBe(60)
            ->and($result->roomName)->toBe('Sala Carvalho');
    });

    it('respeita preferred_start quando o horário está disponível', function () {
        $useCase = new SuggestBookingUseCase(
            suggestRoomRepo([suggestRoom()]),
            suggestBookingRepo([]),
        );

        $result = $useCase->execute(new SuggestBookingInputDTO($this->date, 60, 600)); // 10:00

        expect($result->startTime)->toBe('10:00')
            ->and($result->endTime)->toBe('11:00');
    });

    it('pula uma reserva existente e sugere o próximo slot livre', function () {
        // Reserva 07:00–08:00 → deve sugerir 08:00
        $useCase = new SuggestBookingUseCase(
            suggestRoomRepo([suggestRoom()]),
            suggestBookingRepo([suggestBooking(420, 60, $this->date)]),
        );

        $result = $useCase->execute(new SuggestBookingInputDTO($this->date, 60));

        expect($result->startTime)->toBe('08:00')
            ->and($result->endTime)->toBe('09:00');
    });

    it('pula múltiplas reservas consecutivas', function () {
        // 07:00–08:00 e 08:00–09:00 → deve sugerir 09:00
        $useCase = new SuggestBookingUseCase(
            suggestRoomRepo([suggestRoom()]),
            suggestBookingRepo([
                suggestBooking(420, 60, $this->date),
                suggestBooking(480, 60, $this->date),
            ]),
        );

        $result = $useCase->execute(new SuggestBookingInputDTO($this->date, 60));

        expect($result->startTime)->toBe('09:00');
    });

    it('tenta a próxima sala quando a primeira não tem slot restante após preferred_start', function () {
        $room1 = suggestRoom('a1b2c3d4-0001-0001-0001-000000000001', 'Sala A');
        $room2 = suggestRoom('a1b2c3d4-0002-0002-0002-000000000002', 'Sala B');

        // Estratégia: preferred_start=1140 (19:00), duration=60
        // Sala A tem reserva 19:00–19:30 → após o conflito, cursor vai para 19:30
        //   19:30 + 60 = 20:30 > DAY_END → Sala A não tem slot
        // Sala B está livre → sugere 19:00 na Sala B
        $bookingRepoPerRoom = new class implements BookingRepositoryInterface {
            public function save(Booking $b): void {}
            public function delete(BookingId $id): void {}
            public function findById(BookingId $id): ?Booking { return null; }
            public function findByRoomAndDate(RoomId $r, string $d): array
            {
                if ($r->value() === 'a1b2c3d4-0001-0001-0001-000000000001') {
                    return [Booking::create(
                        BookingId::generate(),
                        $r,
                        BookingPeriod::create($d, 1140, 30), // 19:00–19:30
                        'outro.user',
                    )];
                }
                return []; // Sala B livre
            }
            public function findByRoomAndDateExcluding(RoomId $r, string $d, BookingId $x): array { return []; }
        };

        $useCase = new SuggestBookingUseCase(suggestRoomRepo([$room1, $room2]), $bookingRepoPerRoom);
        $result  = $useCase->execute(new SuggestBookingInputDTO($this->date, 60, 1140)); // 19:00

        expect($result->roomName)->toBe('Sala B')
            ->and($result->startTime)->toBe('19:00');
    });

    it('lança NoAvailableSlotException quando preferred_start não deixa tempo suficiente', function () {
        // 19:30 + 60min = 20:30 → ultrapassa DAY_END → nenhuma sala serve
        $useCase = new SuggestBookingUseCase(
            suggestRoomRepo([suggestRoom()]),
            suggestBookingRepo([]),
        );

        expect(fn () => $useCase->execute(new SuggestBookingInputDTO($this->date, 60, 1170)))
            ->toThrow(NoAvailableSlotException::class);
    });

    it('lança NoAvailableSlotException quando todas as salas estão sem slot para a duração', function () {
        // preferred_start=1140 (19:00), duration=120 → 19:00+120=21:00 → impossível
        $useCase = new SuggestBookingUseCase(
            suggestRoomRepo([suggestRoom('a1b2c3d4-0001-0001-0001-000000000001'), suggestRoom('a1b2c3d4-0002-0002-0002-000000000002')]),
            suggestBookingRepo([]),
        );

        expect(fn () => $useCase->execute(new SuggestBookingInputDTO($this->date, 120, 1140)))
            ->toThrow(NoAvailableSlotException::class);
    });

    it('retorna dados completos na sugestão', function () {
        $useCase = new SuggestBookingUseCase(
            suggestRoomRepo([suggestRoom()]),
            suggestBookingRepo([]),
        );

        $result = $useCase->execute(new SuggestBookingInputDTO($this->date, 90));

        expect($result->date)->toBe($this->date)
            ->and($result->durationMinutes)->toBe(90)
            ->and($result->startMinute)->toBe(420)
            ->and($result->endMinute)->toBe(510)
            ->and($result->startTime)->toBe('07:00')
            ->and($result->endTime)->toBe('08:30')
            ->and($result->roomId)->not->toBeEmpty();
    });
});
