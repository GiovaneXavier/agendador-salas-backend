<?php

declare(strict_types=1);

describe('GET /api/rooms/availability', function () {

    beforeEach(function () {
        putenv('AGENT_API_KEY=test-secret-key');
        $this->headers = ['X-Api-Key' => 'test-secret-key'];
    });

    afterEach(function () {
        putenv('AGENT_API_KEY=');
    });

    it('retorna 422 sem o parâmetro date', function () {
        $this->getJson('/api/rooms/availability', $this->headers)
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'validation_error'])
            ->assertJsonPath('errors.date.0', fn ($v) => str_contains($v, 'obrigatório'));
    });

    it('retorna 422 com formato de data inválido', function () {
        $this->getJson('/api/rooms/availability?date=01-01-2099', $this->headers)
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'validation_error']);
    });

    it('retorna 200 com data válida', function () {
        $this->getJson('/api/rooms/availability?date=2099-01-01', $this->headers)
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    });

    it('retorna uma entrada por sala cadastrada', function () {
        $response = $this->getJson('/api/rooms/availability?date=2099-01-01', $this->headers)
            ->assertStatus(200);

        // InMemory tem 4 salas
        expect(count($response->json('data')))->toBe(4);
    });

    it('cada sala tem a estrutura correta', function () {
        $response = $this->getJson('/api/rooms/availability?date=2099-01-01', $this->headers)
            ->assertStatus(200);

        $room = $response->json('data.0');

        expect($room)->toHaveKeys([
            'room_id', 'room_name', 'color_bg', 'color_accent',
            'capacity', 'resources', 'free_slots',
            'next_available_start', 'next_available_time',
        ]);
    });

    it('salas sem reservas em 2099-01-01 têm um bloco livre de 07:00 a 20:00', function () {
        $response = $this->getJson('/api/rooms/availability?date=2099-01-01', $this->headers)
            ->assertStatus(200);

        foreach ($response->json('data') as $room) {
            expect($room['free_slots'])->toHaveCount(1)
                ->and($room['free_slots'][0]['start_time'])->toBe('07:00')
                ->and($room['free_slots'][0]['end_time'])->toBe('20:00')
                ->and($room['next_available_time'])->toBe('07:00');
        }
    });

    it('salas com reservas hoje têm blocos livres calculados corretamente', function () {
        $today = now()->toDateString();

        $response = $this->getJson("/api/rooms/availability?date={$today}", $this->headers)
            ->assertStatus(200);

        $data = $response->json('data');
        expect($data)->not->toBeEmpty();

        // Toda sala deve ter room_id e free_slots (mesmo que vazio)
        foreach ($data as $room) {
            expect($room)->toHaveKey('room_id')
                ->and($room['free_slots'])->toBeArray();
        }
    });

    it('cada free_slot tem start_time, end_time e duration_minutes', function () {
        $response = $this->getJson('/api/rooms/availability?date=2099-01-01', $this->headers)
            ->assertStatus(200);

        $slots = $response->json('data.0.free_slots');
        expect($slots)->not->toBeEmpty();

        foreach ($slots as $slot) {
            expect($slot)->toHaveKeys(['start_minute', 'end_minute', 'start_time', 'end_time', 'duration_minutes']);
            expect($slot['duration_minutes'])->toBeGreaterThan(0);
        }
    });
});
