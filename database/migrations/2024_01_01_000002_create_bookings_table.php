<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('room_id');
            $table->date('date');                         // 'YYYY-MM-DD'
            $table->integer('start_minute');              // minutos desde meia-noite
            $table->integer('duration_minutes');          // 30 | 60 | 90 | 120
            $table->string('username', 100);              // ex: g.xavier
            $table->timestamps();

            $table->foreign('room_id')
                ->references('id')
                ->on('rooms')
                ->onDelete('cascade');

            // Índice composto — queries por sala + data são frequentes
            $table->index(['room_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
