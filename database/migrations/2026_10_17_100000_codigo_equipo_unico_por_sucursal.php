<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El código de activo deja de ser único en todo el sistema y pasa a ser
 * único POR SUCURSAL: el equipo "101" puede existir a la vez en Clínica
 * San Antonio y en Fundación, pero no dos veces en la misma sucursal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropUnique(['codigo_activo']);
            $table->unique(['sucursal_id', 'codigo_activo']);
            $table->index('codigo_activo');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex(['codigo_activo']);
            $table->dropUnique(['sucursal_id', 'codigo_activo']);
            $table->unique('codigo_activo');
        });
    }
};
