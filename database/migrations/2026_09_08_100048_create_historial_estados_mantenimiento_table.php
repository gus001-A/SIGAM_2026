<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de cambios de estado de una orden. Especificación v2.0 §5.12 / RF-051.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_estados_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_id')->constrained('mantenimientos')->cascadeOnDelete();
            $table->foreignId('estado_origen_id')->nullable()->constrained('estados_mantenimiento')->nullOnDelete();
            $table->foreignId('estado_destino_id')->constrained('estados_mantenimiento')->cascadeOnDelete();
            $table->foreignId('cambiado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nota')->nullable();
            $table->timestamp('cambiado_at')->useCurrent();

            $table->index(['mantenimiento_id', 'cambiado_at'], 'hist_estados_mant_mantenimiento_cambiado_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_estados_mantenimiento');
    }
};
