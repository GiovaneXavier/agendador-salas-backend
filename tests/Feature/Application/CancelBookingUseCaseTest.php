<?php

declare(strict_types=1);

use App\Application\Booking\UseCases\CancelBookingUseCase;
use App\Domain\Booking\Entities\Booking;
use App\Domain\Booking\Exceptions\BookingNotFoundException;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Booking\ValueObjects\BookingId;
use App\Domain\Booking\ValueObjects\BookingPeriod;
use App\Domain\Room\ValueObjects\RoomId;

function makeCancelRepoMock(?Booking $booking, bool &$deleted = false): BookingRepositoryInterface
{
    return new class($booking, $deleted) implements BookingRepositoryInterface {
        private bool $wasDeleted = false;

        public function __construct(
            private ?Booking $storedBooking,
            private bool &$deletedRef,
        ) {}

        public function save(Booking $b): void {}

        public function delete(BookingId $id): void
        {
            $this->deletedRef = true;
        }

        public function findById(BookingId $id): ?Booking
        {
            return $this->storedBooking;
        }

        public function findByRoomAndDate(RoomId $roomId, string $date): array { return []; }
        public function findByRoomAndDateExcluding(RoomId $roomId, string $date, BookingId $excludeId): array { return []; }
    };
}

function makeSampleBooking(string $username = 'g.xavier'): Booking
{
    return Booking::create(
        id:       BookingId::generate(),
        roomId:   RoomId::fromString('a1b2c3d4-0001-0001-0001-000000000001'),
        period:   BookingPeriod::create('2024-06-15', 480, 60),
        username: $username,
    );
}

describe('CancelBookingUseCase', function () {

    it('cancela a reserva quando o username bate com o organizador', function () {
        $booking = makeSampleBooking('g.xavier');
        $deleted = false;
        $repo    = makeCancelRepoMock($booking, $deleted);
        $useCase = new CancelBookingUseCase($repo);

        $useCase->execute($booking->id()->value(), 'g.xavier');

        expect($deleted)->toBeTrue();
    });

    it('a comparação de username é case-insensitive', function () {
        $booking = makeSampleBooking('g.xavier');
        $deleted = false;
        $repo    = makeCancelRepoMock($booking, $deleted);
        $useCase = new CancelBookingUseCase($repo);

        $useCase->execute($booking->id()->value(), 'G.Xavier');

        expect($deleted)->toBeTrue();
    });

    it('lança BookingNotFoundException quando username não é o organizador', function () {
        $booking = makeSampleBooking('g.xavier');
        $deleted = false;
        $repo    = makeCancelRepoMock($booking, $deleted);
        $useCase = new CancelBookingUseCase($repo);

        expect(fn () => $useCase->execute($booking->id()->value(), 'm.silva'))
            ->toThrow(BookingNotFoundException::class);

        expect($deleted)->toBeFalse(); // não deve ter deletado
    });

    it('lança BookingNotFoundException quando a reserva não existe', function () {
        $deleted = false;
        $repo    = makeCancelRepoMock(null, $deleted);
        $useCase = new CancelBookingUseCase($repo);

        expect(fn () => $useCase->execute('id-inexistente', 'g.xavier'))
            ->toThrow(BookingNotFoundException::class);
    });

    it('lança BookingNotFoundException com username vazio ou incorreto', function () {
        $booking = makeSampleBooking('g.xavier');
        $deleted = false;
        $repo    = makeCancelRepoMock($booking, $deleted);
        $useCase = new CancelBookingUseCase($repo);

        expect(fn () => $useCase->execute($booking->id()->value(), 'outrouser'))
            ->toThrow(BookingNotFoundException::class);
    });
});
