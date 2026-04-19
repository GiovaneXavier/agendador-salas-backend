<?php

declare(strict_types=1);

describe('ApiKeyMiddleware', function () {

    beforeEach(function () {
        putenv('AGENT_API_KEY=test-secret-key');
    });

    afterEach(function () {
        putenv('AGENT_API_KEY=');
    });

    // ─── GET /api/rooms/availability ─────────────────────────────────────────

    it('retorna 401 em GET /availability sem X-Api-Key', function () {
        $this->getJson('/api/rooms/availability?date=2099-01-01')
            ->assertStatus(401)
            ->assertJsonFragment(['error' => 'unauthorized']);
    });

    it('retorna 401 em GET /availability com chave errada', function () {
        $this->getJson('/api/rooms/availability?date=2099-01-01', ['X-Api-Key' => 'chave-errada'])
            ->assertStatus(401)
            ->assertJsonFragment(['error' => 'unauthorized']);
    });

    it('passa o middleware em GET /availability com chave correta', function () {
        $this->getJson('/api/rooms/availability?date=2099-01-01', ['X-Api-Key' => 'test-secret-key'])
            ->assertStatus(200);
    });

    // ─── POST /api/bookings/suggest ──────────────────────────────────────────

    it('retorna 401 em POST /suggest sem X-Api-Key', function () {
        $this->postJson('/api/bookings/suggest', ['date' => '2099-01-01', 'duration_minutes' => 60])
            ->assertStatus(401)
            ->assertJsonFragment(['error' => 'unauthorized']);
    });

    it('retorna 401 em POST /suggest com chave errada', function () {
        $this->postJson(
            '/api/bookings/suggest',
            ['date' => '2099-01-01', 'duration_minutes' => 60],
            ['X-Api-Key' => 'chave-errada'],
        )->assertStatus(401);
    });

    it('passa o middleware em POST /suggest com chave correta', function () {
        $this->postJson(
            '/api/bookings/suggest',
            ['date' => '2099-01-01', 'duration_minutes' => 60],
            ['X-Api-Key' => 'test-secret-key'],
        )->assertStatus(200); // InMemory tem salas livres em 2099-01-01
    });

    // ─── Rotas públicas não são afetadas ─────────────────────────────────────

    it('rotas públicas funcionam sem X-Api-Key', function () {
        $this->getJson('/api/rooms')->assertStatus(200);
    });
});
