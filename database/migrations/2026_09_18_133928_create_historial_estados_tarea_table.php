<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de cambios de estado de una tarea — trazabilidad exacta para
 * el auditor (Propuesta técnica SIGAM — anexo "TAREAS"): quién cambió a qué
 * estado, cuándo y con qué nota. Espejo de `historial_estados_mantenimiento`,
 * pero con estados de texto libre (la tarea no usa un catálogo de estados).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_estados_tarea', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->string('estado_origen', 20)->nullable();
            $table->string('estado_destino', 20);
            $table->foreignId('cambiado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('nota')->nullable();
            $table->timestamp('cambiado_at')->useCurrent();

            $table->index(['tarea_id', 'cambiado_at'], 'hist_estados_tarea_tarea_cambiado_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_estados_tarea');
    }
};
