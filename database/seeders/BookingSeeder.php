<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\BookingModel;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $today = now()->toDateString();

        $bookings = [
            // Sala Carvalho
            [
                'id'               => 'b0000001-0001-0001-0001-000000000001',
                'room_id'          => 'a1b2c3d4-0001-0001-0001-000000000001',
                'date'             => $today,
                'start_minute'     => 480,   // 08:00
                'duration_minutes' => 60,
                'username'         => 'm.silva',
            ],
            [
                'id'               => 'b0000001-0002-0002-0002-000000000002',
                'room_id'          => 'a1b2c3d4-0001-0001-0001-000000000001',
                'date'             => $today,
                'start_minute'     => 570,   // 09:30
                'duration_minutes' => 90,
                'username'         => 'time.produto',
            ],
            [
                'id'               => 'b0000001-0003-0003-0003-000000000003',
                'room_id'          => 'a1b2c3d4-0001-0001-0001-000000000001',
                'date'             => $today,
                'start_minute'     => 840,   // 14:00
                'duration_minutes' => 90,
                'username'         => 'c.mendes',
            ],
            // Sala Ipê
            [
                'id'               => 'b0000002-0001-0001-0001-000000000001',
                'room_id'          => 'a1b2c3d4-0002-0002-0002-000000000002',
                'date'             => $today,
                'start_minute'     => 450,   // 07:30
                'duration_minutes' => 60,
                'username'         => 'eng.team',
            ],
            [
                'id'               => 'b0000002-0002-0002-0002-000000000002',
                'room_id'          => 'a1b2c3d4-0002-0002-0002-000000000002',
                'date'             => $today,
                'start_minute'     => 600,   // 10:00
                'duration_minutes' => 90,
                'username'         => 'l.rocha',
            ],
            [
                'id'               => 'b0000002-0003-0003-0003-000000000003',
                'room_id'          => 'a1b2c3d4-0002-0002-0002-000000000002',
                'date'             => $today,
                'start_minute'     => 900,   // 15:00
                'duration_minutes' => 60,
                'username'         => 'a.beatriz',
            ],
        ];

        foreach ($bookings as $data) {
            BookingModel::updateOrCreate(['id' => $data['id']], $data);
        }
    }
}
