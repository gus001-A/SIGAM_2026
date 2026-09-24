<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Responsables asignados a una tarea. Admite varios; es_principal marca al
 * responsable principal. Espejo de `asignaciones_mantenimiento`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarea_responsables', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('asignado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->boolean('es_principal')->default(false);
            $table->timestamp('asignado_at')->useCurrent();
            $table->timestamp('desasignado_at')->nullable();
            $table->string('notas')->nullable();

            $table->index(['tarea_id', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarea_responsables');
    }
};
