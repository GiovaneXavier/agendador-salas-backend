<?php

declare(strict_types=1);

use App\Application\Booking\DTOs\CreateBookingInputDTO;
use App\Application\Booking\UseCases\CreateBookingUseCase;
use App\Domain\Booking\Entities\Booking;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Booking\Services\BookingConflictService;
use App\Domain\Booking\ValueObjects\BookingId;
use App\Domain\Booking\ValueObjects\BookingPeriod;
use App\Domain\Room\Entities\Room;
use App\Domain\Room\Exceptions\RoomNotFoundException;
use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Domain\Room\ValueObjects\RoomId;

// Mock do RoomRepository — por padrão devolve uma sala válida
function makeRoomRepoMock(bool $roomExists = true): RoomRepositoryInterface
{
    return new class($roomExists) implements RoomRepositoryInterface {
        public function __construct(private bool $exists) {}
        public function findAll(): array { return []; }
        public function findById(RoomId $id): ?Room
        {
            return $this->exists
                ? Room::reconstitute($id->value(), 'Sala Teste', '#eae4dc', '#4a3d2f')
                : null;
        }
    };
}

// Mock do BookingRepository
function makeBookingRepoMock(array $existingBookings = [], ?Booking &$savedBooking = null): BookingRepositoryInterface
{
    return new class($existingBookings, $savedBooking) implements BookingRepositoryInterface {
        private ?Booking $ref;

        public function __construct(
            private array $existing,
            ?Booking &$ref,
        ) {
            $this->ref = &$ref;
        }

        public function save(Booking $booking): void
        {
            $this->ref = $booking;
        }

        public function delete(BookingId $id): void {}

        public function findById(BookingId $id): ?Booking { return null; }

        public function findByRoomAndDate(RoomId $roomId, string $date): array
        {
            return $this->existing;
        }

        public function findByRoomAndDateExcluding(RoomId $roomId, string $date, BookingId $excludeId): array
        {
            return $this->existing;
        }
    };
}

describe('CreateBookingUseCase', function () {

    beforeEach(function () {
        $this->roomId = 'a1b2c3d4-0001-0001-0001-000000000001';
        $this->date   = '2024-06-15';
    });

    it('cria uma reserva com sucesso quando não há conflito', function () {
        $savedBooking = null;
        $repo         = makeBookingRepoMock([], $savedBooking);
        $useCase      = new CreateBookingUseCase($repo, makeRoomRepoMock(), new BookingConflictService());

        $input = new CreateBookingInputDTO(
            roomId:          $this->roomId,
            date:            $this->date,
            startMinute:     480,
            durationMinutes: 60,
            username:        'g.xavier',
        );

        $output = $useCase->execute($input);

        expect($output->roomId)->toBe($this->roomId)
            ->and($output->date)->toBe($this->date)
            ->and($output->startMinute)->toBe(480)
            ->and($output->durationMinutes)->toBe(60)
            ->and($output->startTime)->toBe('08:00')
            ->and($output->endTime)->toBe('09:00')
            ->and($output->username)->toBe('g.xavier')
            ->and($output->id)->not->toBeEmpty();

        expect($savedBooking)->not->toBeNull();
    });

    it('lança SlotUnavailableException quando há conflito de horário', function () {
        $conflict = Booking::create(
            id:       BookingId::generate(),
            roomId:   RoomId::fromString($this->roomId),
            period:   BookingPeriod::create($this->date, 480, 60),
            username: 'm.silva',
        );

        $savedBooking = null;
        $repo         = makeBookingRepoMock([$conflict], $savedBooking);
        $useCase      = new CreateBookingUseCase($repo, makeRoomRepoMock(), new BookingConflictService());

        $input = new CreateBookingInputDTO(
            roomId:          $this->roomId,
            date:            $this->date,
            startMinute:     480,   // mesmo horário
            durationMinutes: 30,
            username:        'g.xavier',
        );

        expect(fn () => $useCase->execute($input))
            ->toThrow(SlotUnavailableException::class);

        expect($savedBooking)->toBeNull(); // não deve ter sido salvo
    });

    it('lança SlotUnavailableException quando o novo slot começa no meio de um existente', function () {
        $conflict = Booking::create(
            id:       BookingId::generate(),
            roomId:   RoomId::fromString($this->roomId),
            period:   BookingPeriod::create($this->date, 480, 90), // 08:00–09:30
            username: 'm.silva',
        );

        $savedBooking = null;
        $repo         = makeBookingRepoMock([$conflict], $savedBooking);
        $useCase      = new CreateBookingUseCase($repo, makeRoomRepoMock(), new BookingConflictService());

        $input = new CreateBookingInputDTO(
            roomId:          $this->roomId,
            date:            $this->date,
            startMinute:     510, // 08:30 — dentro do bloco existente
            durationMinutes: 30,
            username:        'g.xavier',
        );

        expect(fn () => $useCase->execute($input))
            ->toThrow(SlotUnavailableException::class);
    });

    it('cria reserva com sucesso quando slot é adjacente (não sobrepõe)', function () {
        $existing = Booking::create(
            id:       BookingId::generate(),
            roomId:   RoomId::fromString($this->roomId),
            period:   BookingPeriod::create($this->date, 480, 60), // 08:00–09:00
            username: 'm.silva',
        );

        $savedBooking = null;
        $repo         = makeBookingRepoMock([$existing], $savedBooking);
        $useCase      = new CreateBookingUseCase($repo, makeRoomRepoMock(), new BookingConflictService());

        $input = new CreateBookingInputDTO(
            roomId:          $this->roomId,
            date:            $this->date,
            startMinute:     540, // 09:00 — imediatamente após
            durationMinutes: 30,
            username:        'g.xavier',
        );

        $output = $useCase->execute($input);

        expect($output->startTime)->toBe('09:00')
            ->and($output->endTime)->toBe('09:30');
    });

    it('lança RoomNotFoundException quando a sala não existe', function () {
        $savedBooking = null;
        $repo         = makeBookingRepoMock([], $savedBooking);
        $useCase      = new CreateBookingUseCase($repo, makeRoomRepoMock(false), new BookingConflictService());

        $input = new CreateBookingInputDTO(
            roomId:          $this->roomId,
            date:            $this->date,
            startMinute:     480,
            durationMinutes: 30,
            username:        'g.xavier',
        );

        expect(fn () => $useCase->execute($input))
            ->toThrow(RoomNotFoundException::class);

        expect($savedBooking)->toBeNull();
    });

    it('lança InvalidArgumentException para duração inválida via BookingPeriod', function () {
        $savedBooking = null;
        $repo         = makeBookingRepoMock([], $savedBooking);
        $useCase      = new CreateBookingUseCase($repo, makeRoomRepoMock(), new BookingConflictService());

        $input = new CreateBookingInputDTO(
            roomId:          $this->roomId,
            date:            $this->date,
            startMinute:     480,
            durationMinutes: 45, // inválido
            username:        'g.xavier',
        );

        expect(fn () => $useCase->execute($input))
            ->toThrow(\InvalidArgumentException::class);
    });
});
