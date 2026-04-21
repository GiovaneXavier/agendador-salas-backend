<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Booking\Events\BookingCancelled;
use App\Domain\Booking\Events\BookingCreated;
use App\Domain\Booking\Events\BookingExtended;
use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Infrastructure\Persistence\InMemory\InMemoryBookingRepository;
use App\Infrastructure\Persistence\InMemory\InMemoryRoomRepository;
use App\Listeners\Booking\SendBookingCancelledWebhook;
use App\Listeners\Booking\SendBookingCreatedWebhook;
use App\Listeners\Booking\SendBookingExtendedWebhook;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositórios in-memory (sem banco de dados)
        // Troque por EloquentRoomRepository / EloquentBookingRepository quando o banco estiver configurado.
        $this->app->singleton(RoomRepositoryInterface::class, InMemoryRoomRepository::class);
        $this->app->singleton(BookingRepositoryInterface::class, InMemoryBookingRepository::class);
    }

    public function boot(): void
    {
        Event::listen(BookingCreated::class, SendBookingCreatedWebhook::class);
        Event::listen(BookingExtended::class, SendBookingExtendedWebhook::class);
        Event::listen(BookingCancelled::class, SendBookingCancelledWebhook::class);
    }
}
