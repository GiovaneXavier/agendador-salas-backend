<?php

declare(strict_types=1);

use App\Domain\Booking\Entities\Booking;
use App\Domain\Booking\Exceptions\SlotUnavailableException;
use App\Domain\Booking\Services\BookingConflictService;
use App\Domain\Booking\ValueObjects\BookingId;
use App\Domain\Booking\ValueObjects\BookingPeriod;
use App\Domain\Room\ValueObjects\RoomId;

// Helper para criar Booking de teste
function makeBooking(string $date, int $start, int $duration): Booking
{
    return Booking::create(
        id:       BookingId::generate(),
        roomId:   RoomId::fromString('a1b2c3d4-0001-0001-0001-000000000001'),
        period:   BookingPeriod::create($date, $start, $duration),
        username: 'test.user',
    );
}

// Helper para criar BookingPeriod de teste
function makePeriod(string $date, int $start, int $duration): BookingPeriod
{
    return BookingPeriod::create($date, $start, $duration);
}

describe('BookingConflictService', function () {

    beforeEach(function () {
        $this->service = new BookingConflictService();
        $this->date    = '2024-06-15';
    });

    it('não lança exceção quando não há reservas existentes', function () {
        $desired = makePeriod($this->date, 480, 60); // 08:00–09:00

        expect(fn () => $this->service->assertNoConflict($desired, []))->not->toThrow(SlotUnavailableException::class);
    });

    it('não lança exceção quando as reservas não se sobrepõem', function () {
        $existing = [
            makeBooking($this->date, 420, 60),  // 07:00–08:00
            makeBooking($this->date, 540, 60),  // 09:00–10:00
        ];
        $desired = makePeriod($this->date, 480, 60); // 08:00–09:00 — entre as duas

        expect(fn () => $this->service->assertNoConflict($desired, $existing))->not->toThrow(SlotUnavailableException::class);
    });

    it('lança SlotUnavailableException quando há sobreposição exata', function () {
        $existing = [makeBooking($this->date, 480, 60)]; // 08:00–09:00
        $desired  = makePeriod($this->date, 480, 60);    // 08:00–09:00

        expect(fn () => $this->service->assertNoConflict($desired, $existing))
            ->toThrow(SlotUnavailableException::class);
    });

    it('lança exceção quando o novo slot começa dentro de uma reserva existente', function () {
        $existing = [makeBooking($this->date, 480, 60)]; // 08:00–09:00
        $desired  = makePeriod($this->date, 510, 30);    // 08:30–09:00 — começa dentro

        expect(fn () => $this->service->assertNoConflict($desired, $existing))
            ->toThrow(SlotUnavailableException::class);
    });

    it('lança exceção quando o novo slot engloba uma reserva existente', function () {
        $existing = [makeBooking($this->date, 510, 30)]; // 08:30–09:00
        $desired  = makePeriod($this->date, 480, 90);    // 08:00–09:30 — engloba

        expect(fn () => $this->service->assertNoConflict($desired, $existing))
            ->toThrow(SlotUnavailableException::class);
    });

    it('não há conflito quando o novo slot termina exatamente quando o existente começa', function () {
        $existing = [makeBooking($this->date, 540, 60)]; // 09:00–10:00
        $desired  = makePeriod($this->date, 480, 60);    // 08:00–09:00 — adjacente

        expect(fn () => $this->service->assertNoConflict($desired, $existing))->not->toThrow(SlotUnavailableException::class);
    });

    it('não há conflito quando o existente termina exatamente quando o novo começa', function () {
        $existing = [makeBooking($this->date, 420, 60)]; // 07:00–08:00
        $desired  = makePeriod($this->date, 480, 60);    // 08:00–09:00 — adjacente

        expect(fn () => $this->service->assertNoConflict($desired, $existing))->not->toThrow(SlotUnavailableException::class);
    });

    it('ignora reservas em outras datas', function () {
        $existing = [makeBooking('2024-06-14', 480, 60)]; // outro dia, mesmo horário
        $desired  = makePeriod($this->date, 480, 60);

        expect(fn () => $this->service->assertNoConflict($desired, $existing))->not->toThrow(SlotUnavailableException::class);
    });

    it('hasConflict retorna true quando há sobreposição', function () {
        $existing = [makeBooking($this->date, 480, 60)];
        $desired  = makePeriod($this->date, 510, 30);

        expect($this->service->hasConflict($desired, $existing))->toBeTrue();
    });

    it('hasConflict retorna false quando não há sobreposição', function () {
        $existing = [makeBooking($this->date, 480, 60)];
        $desired  = makePeriod($this->date, 540, 30);

        expect($this->service->hasConflict($desired, $existing))->toBeFalse();
    });
});
