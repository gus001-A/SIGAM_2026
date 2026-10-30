<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comentario de supervisión sobre un movimiento del historial de una tarea
 * (no sobre la tarea en general): va "dentro" de ese movimiento, igual que
 * la nota que escribió el responsable al hacerlo. Solo lo escriben
 * supervisores y auditores, y solo lo ven ellos mismos y los responsables
 * activos de la tarea — nadie más. Es de solo lectura una vez publicado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comentarios_tarea', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->foreignId('historial_estado_tarea_id')->constrained('historial_estados_tarea')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->text('cuerpo');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['historial_estado_tarea_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comentarios_tarea');
    }
};
