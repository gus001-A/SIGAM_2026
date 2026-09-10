<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de cambios de ubicación de un equipo. RF-035 / Especificación §8.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_ubicacion_equipo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('ubicacion_origen_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $table->foreignId('ubicacion_destino_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $table->foreignId('cambiado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('motivo')->nullable();
            $table->timestamp('cambiado_at')->useCurrent();

            $table->index(['equipo_id', 'cambiado_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_ubicacion_equipo');
    }
};
