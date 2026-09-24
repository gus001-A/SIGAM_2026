<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Especialidades de cada usuario técnico (tipos de equipo y tipos de
 * mantenimiento en los que es bueno) — para sugerir al técnico más
 * adecuado al delegar una orden de mantenimiento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_tipo_equipo', function (Blueprint $table): void {
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('tipo_equipo_id')->constrained('tipos_equipo')->cascadeOnDelete();
            $table->primary(['usuario_id', 'tipo_equipo_id']);
        });

        Schema::create('usuario_tipo_mantenimiento', function (Blueprint $table): void {
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('tipo_mantenimiento_id')->constrained('tipos_mantenimiento')->cascadeOnDelete();
            $table->primary(['usuario_id', 'tipo_mantenimiento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_tipo_mantenimiento');
        Schema::dropIfExists('usuario_tipo_equipo');
    }
};
