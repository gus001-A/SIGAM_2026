<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite adjuntar un enlace (opcional) a un avance de tarea o a un
 * movimiento de la bitácora de una orden, igual que ya se puede adjuntar
 * una foto o un documento. Nunca pasa por mayúsculas (sería una liga rota).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historial_estados_tarea', function (Blueprint $table) {
            $table->string('enlace', 2048)->nullable()->after('nota');
        });

        Schema::table('observaciones_mantenimiento', function (Blueprint $table) {
            $table->string('enlace', 2048)->nullable()->after('cuerpo');
        });
    }

    public function down(): void
    {
        Schema::table('historial_estados_tarea', function (Blueprint $table) {
            $table->dropColumn('enlace');
        });

        Schema::table('observaciones_mantenimiento', function (Blueprint $table) {
            $table->dropColumn('enlace');
        });
    }
};
