<?php

declare(strict_types=1);

use App\Application\Room\UseCases\GetRoomsAvailabilityUseCase;
use App\Domain\Booking\Entities\Booking;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Booking\ValueObjects\BookingId;
use App\Domain\Booking\ValueObjects\BookingPeriod;
use App\Domain\Room\Entities\Room;
use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Domain\Room\ValueObjects\RoomId;

function availRoomRepo(array $rooms): RoomRepositoryInterface
{
    return new class($rooms) implements RoomRepositoryInterface {
        public function __construct(private array $rooms) {}
        public function findAll(): array        { return $this->rooms; }
        public function findById(RoomId $id): ?Room { return null; }
    };
}

function availBookingRepo(array $bookings): BookingRepositoryInterface
{
    return new class($bookings) implements BookingRepositoryInterface {
        public function __construct(private array $bookings) {}
        public function save(Booking $b): void  {}
        public function delete(BookingId $id): void {}
        public function findById(BookingId $id): ?Booking { return null; }
        public function findByRoomAndDate(RoomId $r, string $d): array { return $this->bookings; }
        public function findByRoomAndDateExcluding(RoomId $r, string $d, BookingId $x): array { return []; }
    };
}

function availTestRoom(string $id = 'a1b2c3d4-0001-0001-0001-000000000001', string $name = 'Sala Teste'): Room
{
    return Room::reconstitute($id, $name, '#eae4dc', '#4a3d2f', 8, ['TV']);
}

function availTestBooking(int $start, int $duration, string $date = '2099-01-01'): Booking
{
    return Booking::create(
        id:       BookingId::generate(),
        roomId:   RoomId::fromString('a1b2c3d4-0001-0001-0001-000000000001'),
        period:   BookingPeriod::create($date, $start, $duration),
        username: 'test.user',
    );
}

describe('GetRoomsAvailabilityUseCase', function () {

    beforeEach(function () {
        $this->date = '2099-01-01';
    });

    it('retorna lista vazia quando não há salas', function () {
        $useCase = new GetRoomsAvailabilityUseCase(availRoomRepo([]), availBookingRepo([]));
        $result  = $useCase->execute($this->date);

        expect($result)->toBeArray()->toBeEmpty();
    });

    it('retorna um bloco livre de 07:00 a 20:00 quando não há reservas', function () {
        $room    = availTestRoom();
        $useCase = new GetRoomsAvailabilityUseCase(availRoomRepo([$room]), availBookingRepo([]));
        $result  = $useCase->execute($this->date);

        expect($result)->toHaveCount(1);

        $r = $result[0];
        expect($r['free_slots'])->toHaveCount(1)
            ->and($r['free_slots'][0]['start_time'])->toBe('07:00')
            ->and($r['free_slots'][0]['end_time'])->toBe('20:00')
            ->and($r['free_slots'][0]['duration_minutes'])->toBe(780)
            ->and($r['next_available_time'])->toBe('07:00');
    });

    it('calcula dois blocos livres ao redor de uma reserva central', function () {
        // Reserva 08:00–09:00 → livre 07:00–08:00 e 09:00–20:00
        $booking = availTestBooking(480, 60, $this->date);
        $useCase = new GetRoomsAvailabilityUseCase(availRoomRepo([availTestRoom()]), availBookingRepo([$booking]));
        $result  = $useCase->execute($this->date);

        $slots = $result[0]['free_slots'];
        expect($slots)->toHaveCount(2)
            ->and($slots[0]['start_time'])->toBe('07:00')
            ->and($slots[0]['end_time'])->toBe('08:00')
            ->and($slots[0]['duration_minutes'])->toBe(60)
            ->and($slots[1]['start_time'])->toBe('09:00')
            ->and($slots[1]['end_time'])->toBe('20:00')
            ->and($slots[1]['duration_minutes'])->toBe(660);
    });

    it('não há bloco livre antes de uma reserva que começa às 07:00', function () {
        $booking = availTestBooking(420, 60, $this->date); // 07:00–08:00
        $useCase = new GetRoomsAvailabilityUseCase(availRoomRepo([availTestRoom()]), availBookingRepo([$booking]));
        $result  = $useCase->execute($this->date);

        $slots = $result[0]['free_slots'];
        expect($slots)->toHaveCount(1)
            ->and($slots[0]['start_time'])->toBe('08:00')
            ->and($slots[0]['end_time'])->toBe('20:00');
    });

    it('next_available_time é null quando todos os slots estão bloqueados', function () {
        // Simula um repo que bloqueia todo o período — sem slots livres
        $bookingRepo = new class implements BookingRepositoryInterface {
            public function save(Booking $b): void  {}
            public function delete(BookingId $id): void {}
            public function findById(BookingId $id): ?Booking { return null; }
            public function findByRoomAndDate(RoomId $r, string $d): array
            {
                // Encobre 07:00–20:00 com múltiplas reservas de 120min
                $date = $d;
                return [
                    Booking::create(BookingId::generate(), $r, BookingPeriod::create($date, 420,  120), 'u'),
                    Booking::create(BookingId::generate(), $r, BookingPeriod::create($date, 540,  120), 'u'),
                    Booking::create(BookingId::generate(), $r, BookingPeriod::create($date, 660,  120), 'u'),
                    Booking::create(BookingId::generate(), $r, BookingPeriod::create($date, 780,  120), 'u'),
                    Booking::create(BookingId::generate(), $r, BookingPeriod::create($date, 900,  120), 'u'),
                    Booking::create(BookingId::generate(), $r, BookingPeriod::create($date, 1020, 120), 'u'),
                    Booking::create(BookingId::generate(), $r, BookingPeriod::create($date, 1140,  60), 'u'),
                ];
            }
            public function findByRoomAndDateExcluding(RoomId $r, string $d, BookingId $x): array { return []; }
        };

        $useCase = new GetRoomsAvailabilityUseCase(availRoomRepo([availTestRoom()]), $bookingRepo);
        $result  = $useCase->execute($this->date);

        expect($result[0]['free_slots'])->toBeEmpty()
            ->and($result[0]['next_available_time'])->toBeNull();
    });

    it('inclui metadados corretos da sala na resposta', function () {
        $room    = Room::reconstitute('a1b2c3d4-0001-0001-0001-000000000001', 'Sala Carvalho', '#eae4dc', '#4a3d2f', 8, ['TV', 'HDMI']);
        $useCase = new GetRoomsAvailabilityUseCase(availRoomRepo([$room]), availBookingRepo([]));
        $result  = $useCase->execute($this->date);

        $r = $result[0];
        expect($r['room_id'])->toBe('a1b2c3d4-0001-0001-0001-000000000001')
            ->and($r['room_name'])->toBe('Sala Carvalho')
            ->and($r['capacity'])->toBe(8)
            ->and($r['resources'])->toBe(['TV', 'HDMI'])
            ->and($r['color_bg'])->toBe('#eae4dc')
            ->and($r['color_accent'])->toBe('#4a3d2f');
    });

    it('processa múltiplas salas independentemente', function () {
        $room1 = availTestRoom('a1b2c3d4-0001-0001-0001-000000000001', 'Sala A');
        $room2 = availTestRoom('a1b2c3d4-0002-0002-0002-000000000002', 'Sala B');

        $useCase = new GetRoomsAvailabilityUseCase(availRoomRepo([$room1, $room2]), availBookingRepo([]));
        $result  = $useCase->execute($this->date);

        expect($result)->toHaveCount(2)
            ->and($result[0]['room_name'])->toBe('Sala A')
            ->and($result[1]['room_name'])->toBe('Sala B');
    });
});
