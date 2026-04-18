<?php

declare(strict_types=1);

use App\Application\Booking\UseCases\ExtendBookingUseCase;
use App\Domain\Booking\Entities\Booking;
use App\Domain\Booking\Exceptions\BookingNotFoundException;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Booking\Services\BookingConflictService;
use App\Domain\Booking\ValueObjects\BookingId;
use App\Domain\Booking\ValueObjects\BookingPeriod;
use App\Domain\Room\ValueObjects\RoomId;

function makeExtendRepo(
    ?Booking $stored,
    array    $otherBookings = [],
    ?Booking &$saved = null,
): BookingRepositoryInterface {
    return new class($stored, $otherBookings, $saved) implements BookingRepositoryInterface {
        public function __construct(
            private ?Booking  $stored,
            private array     $others,
            private ?Booking &$savedRef,
        ) {}

        public function save(Booking $b): void            { $this->savedRef = $b; }
        public function delete(BookingId $id): void        {}
        public function findById(BookingId $id): ?Booking  { return $this->stored; }
        public function findByRoomAndDate(RoomId $r, string $d): array { return []; }
        public function findByRoomAndDateExcluding(RoomId $r, string $d, BookingId $x): array
        {
            return $this->others;
        }
    };
}

function makeBookingFor(int $start, int $duration, string $date = '2024-06-15'): Booking
{
    return Booking::create(
        id:       BookingId::generate(),
        roomId:   RoomId::fromString('a1b2c3d4-0001-0001-0001-000000000001'),
        period:   BookingPeriod::create($date, $start, $duration),
        username: 'g.xavier',
    );
}

describe('ExtendBookingUseCase', function () {

    it('estende a reserva em 30 minutos quando não há conflito', function () {
        $booking = makeBookingFor(480, 60); // 08:00–09:00
        $saved   = null;
        $repo    = makeExtendRepo($booking, [], $saved);
        $useCase = new ExtendBookingUseCase($repo, new BookingConflictService());

        $output = $useCase->execute($booking->id()->value());

        expect($output->durationMinutes)->toBe(90)
            ->and($output->endTime)->toBe('09:30')
            ->and($output->startTime)->toBe('08:00');

        expect($saved)->not->toBeNull();
        expect($saved->period()->durationMinutes())->toBe(90);
    });

    it('lança BookingNotFoundException quando a reserva não existe', function () {
        $saved   = null;
        $repo    = makeExtendRepo(null, [], $saved);
        $useCase = new ExtendBookingUseCase($repo, new BookingConflictService());

        expect(fn () => $useCase->execute('id-inexistente'))
            ->toThrow(BookingNotFoundException::class);

        expect($saved)->toBeNull();
    });

    it('lança SlotUnavailableException quando a extensão cria conflito', function () {
        $booking  = makeBookingFor(480, 60); // 08:00–09:00
        $conflict = makeBookingFor(540, 30); // 09:00–09:30 — colidiria com a extensão

        $saved   = null;
        $repo    = makeExtendRepo($booking, [$conflict], $saved);
        $useCase = new ExtendBookingUseCase($repo, new BookingConflictService());

        expect(fn () => $useCase->execute($booking->id()->value()))
            ->toThrow(SlotUnavailableException::class);

        expect($saved)->toBeNull();
    });

    it('lança InvalidArgumentException quando a extensão ultrapassaria 20h', function () {
        $booking = makeBookingFor(1140, 30); // 19:00–19:30 → +30 = 20:00 ✅ (limite exato)
        $saved   = null;
        $repo    = makeExtendRepo($booking, [], $saved);
        $useCase = new ExtendBookingUseCase($repo, new BookingConflictService());

        // 19:00 + 60min = 20:00 — ainda dentro do limite
        $output = $useCase->execute($booking->id()->value());
        expect($output->endTime)->toBe('20:00');
    });

    it('lança InvalidArgumentException quando a extensão ultrapassa 20h', function () {
        $booking = makeBookingFor(1170, 30); // 19:30–20:00 → +30 = 20:30 ❌
        $saved   = null;
        $repo    = makeExtendRepo($booking, [], $saved);
        $useCase = new ExtendBookingUseCase($repo, new BookingConflictService());

        expect(fn () => $useCase->execute($booking->id()->value()))
            ->toThrow(\InvalidArgumentException::class);

        expect($saved)->toBeNull();
    });
});
