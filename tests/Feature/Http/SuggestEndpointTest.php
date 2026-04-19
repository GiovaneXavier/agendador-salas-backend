<?php

declare(strict_types=1);

describe('POST /api/bookings/suggest', function () {

    beforeEach(function () {
        putenv('AGENT_API_KEY=test-secret-key');
        $this->headers = ['X-Api-Key' => 'test-secret-key'];
        $this->freeDate = '2099-01-01'; // sem reservas no InMemory
    });

    afterEach(function () {
        putenv('AGENT_API_KEY=');
    });

    // ─── Validação ────────────────────────────────────────────────────────────

    it('retorna 422 sem nenhum campo', function () {
        $this->postJson('/api/bookings/suggest', [], $this->headers)
            ->assertStatus(422)
            ->assertJsonFragment(['error' => 'validation_error']);
    });

    it('retorna 422 sem date', function () {
        $this->postJson('/api/bookings/suggest', ['duration_minutes' => 60], $this->headers)
            ->assertStatus(422)
            ->assertJsonPath('errors.date.0', fn ($v) => str_contains($v, 'obrigatório'));
    });

    it('retorna 422 sem duration_minutes', function () {
        $this->postJson('/api/bookings/suggest', ['date' => $this->freeDate], $this->headers)
            ->assertStatus(422)
            ->assertJsonPath('errors.duration_minutes.0', fn ($v) => str_contains($v, 'obrigatório'));
    });

    it('retorna 422 com duração inválida', function () {
        $this->postJson('/api/bookings/suggest', ['date' => $this->freeDate, 'duration_minutes' => 45], $this->headers)
            ->assertStatus(422)
            ->assertJsonPath('errors.duration_minutes.0', fn ($v) => str_contains($v, '30, 60, 90 ou 120'));
    });

    it('retorna 422 com formato de data inválido', function () {
        $this->postJson('/api/bookings/suggest', ['date' => '01/01/2099', 'duration_minutes' => 60], $this->headers)
            ->assertStatus(422);
    });

    it('retorna 422 com preferred_start fora do expediente', function () {
        $this->postJson('/api/bookings/suggest', [
            'date'             => $this->freeDate,
            'duration_minutes' => 60,
            'preferred_start'  => 300, // antes das 07:00
        ], $this->headers)->assertStatus(422);
    });

    // ─── Sucesso ──────────────────────────────────────────────────────────────

    it('retorna 200 com sugestão válida para data sem reservas', function () {
        $this->postJson('/api/bookings/suggest', [
            'date'             => $this->freeDate,
            'duration_minutes' => 60,
        ], $this->headers)->assertStatus(200);
    });

    it('resposta tem a estrutura correta', function () {
        $response = $this->postJson('/api/bookings/suggest', [
            'date'             => $this->freeDate,
            'duration_minutes' => 60,
        ], $this->headers)->assertStatus(200);

        $data = $response->json('data');
        expect($data)->toHaveKeys([
            'room_id', 'room_name', 'date',
            'start_minute', 'end_minute', 'start_time', 'end_time',
            'duration_minutes',
        ]);
    });

    it('sugere 07:00 quando não há reservas e sem preferred_start', function () {
        $response = $this->postJson('/api/bookings/suggest', [
            'date'             => $this->freeDate,
            'duration_minutes' => 60,
        ], $this->headers)->assertStatus(200);

        expect($response->json('data.start_time'))->toBe('07:00')
            ->and($response->json('data.end_time'))->toBe('08:00')
            ->and($response->json('data.duration_minutes'))->toBe(60)
            ->and($response->json('data.date'))->toBe($this->freeDate);
    });

    it('respeita preferred_start', function () {
        $response = $this->postJson('/api/bookings/suggest', [
            'date'             => $this->freeDate,
            'duration_minutes' => 30,
            'preferred_start'  => 720, // 12:00
        ], $this->headers)->assertStatus(200);

        expect($response->json('data.start_time'))->toBe('12:00')
            ->and($response->json('data.end_time'))->toBe('12:30');
    });

    // ─── Sem slot disponível ──────────────────────────────────────────────────

    it('retorna 404 quando preferred_start não deixa tempo suficiente para a duração', function () {
        // 19:30 + 60min = 20:30 → além das 20:00
        $this->postJson('/api/bookings/suggest', [
            'date'             => $this->freeDate,
            'duration_minutes' => 60,
            'preferred_start'  => 1170, // 19:30
        ], $this->headers)
            ->assertStatus(404)
            ->assertJsonFragment(['error' => 'no_available_slot']);
    });
});
