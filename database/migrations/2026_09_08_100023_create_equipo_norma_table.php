<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relación N:M equipo <-> normas aplicables. Especificación §9 / §5.14.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipo_norma', function (Blueprint $table) {
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('norma_id')->constrained('normas')->cascadeOnDelete();
            $table->primary(['equipo_id', 'norma_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_norma');
    }
};
