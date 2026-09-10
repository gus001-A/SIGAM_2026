<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de reprogramaciones. Especificación v2.0 §5.11 / §7 / RF-051.
 * Regla de negocio: una reprogramación exige motivo y queda en auditoría.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reprogramaciones_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_id')->constrained('mantenimientos')->cascadeOnDelete();
            $table->timestamp('inicio_anterior')->nullable();
            $table->timestamp('fin_anterior')->nullable();
            $table->timestamp('inicio_nuevo')->nullable();
            $table->timestamp('fin_nuevo')->nullable();
            $table->string('motivo');
            $table->foreignId('reprogramado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reprogramaciones_mantenimiento');
    }
};
