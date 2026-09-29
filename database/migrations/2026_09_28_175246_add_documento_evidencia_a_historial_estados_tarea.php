<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liga cada movimiento del historial con la foto de evidencia que se
 * adjuntó al hacer la transición, para poder mostrarla directamente desde
 * el historial (sin la sección aparte de "Documentos" que tenía la tarea).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_estados_tarea', function (Blueprint $table) {
            $table->foreignId('documento_evidencia_id')->nullable()->after('nota')
                ->constrained('documentos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('historial_estados_tarea', function (Blueprint $table) {
            $table->dropConstrainedForeignId('documento_evidencia_id');
        });
    }
};
