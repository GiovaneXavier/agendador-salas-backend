<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Booking\Repositories\BookingRepositoryInterface;
use App\Domain\Room\Repositories\RoomRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentBookingRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentRoomRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Repository Interfaces → Eloquent Implementations
        $this->app->bind(
            RoomRepositoryInterface::class,
            EloquentRoomRepository::class,
        );

        $this->app->bind(
            BookingRepositoryInterface::class,
            EloquentBookingRepository::class,
        );
    }

    public function boot(): void {}
}
