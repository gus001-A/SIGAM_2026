<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Técnicos asignados a una orden de mantenimiento. Especificación v2.0 §8 / RF-044.
 * Admite varios técnicos; es_principal marca al responsable principal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_id')->constrained('mantenimientos')->cascadeOnDelete();
            $table->foreignId('tecnico_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('asignado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->boolean('es_principal')->default(false);
            $table->timestamp('asignado_at')->useCurrent();
            $table->timestamp('desasignado_at')->nullable();
            $table->string('notas')->nullable();

            $table->index(['mantenimiento_id', 'tecnico_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_mantenimiento');
    }
};
