<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Models\RoomModel;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            [
                'id'           => 'a1b2c3d4-0001-0001-0001-000000000001',
                'name'         => 'Sala Carvalho',
                'color_bg'     => '#eae4dc',
                'color_accent' => '#4a3d2f',
                'capacity'     => 8,
                'resources'    => ['TV', 'HDMI'],
            ],
            [
                'id'           => 'a1b2c3d4-0002-0002-0002-000000000002',
                'name'         => 'Sala Ipê',
                'color_bg'     => '#e2e6df',
                'color_accent' => '#2f4a3d',
                'capacity'     => 4,
                'resources'    => ['QUADRO BRANCO'],
            ],
            [
                'id'           => 'a1b2c3d4-0003-0003-0003-000000000003',
                'name'         => 'Sala Aroeira',
                'color_bg'     => '#e4e0f0',
                'color_accent' => '#3d2f6e',
                'capacity'     => 12,
                'resources'    => ['PROJETOR', 'VIDEOCONFERÊNCIA'],
            ],
            [
                'id'           => 'a1b2c3d4-0004-0004-0004-000000000004',
                'name'         => 'Sala Cedro',
                'color_bg'     => '#dfe8e4',
                'color_accent' => '#2f4a42',
                'capacity'     => 6,
                'resources'    => ['TV', 'QUADRO BRANCO'],
            ],
        ];

        foreach ($rooms as $data) {
            RoomModel::updateOrCreate(['id' => $data['id']], $data);
        }
    }
}
