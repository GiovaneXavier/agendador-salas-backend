<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            // SQL Server: uniqueidentifier para UUID
            $table->uuid('id')->primary();
            $table->string('name', 200);
            $table->string('color_bg', 20)->default('#f5f3ef');
            $table->string('color_accent', 20)->default('#4a3d2f');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
