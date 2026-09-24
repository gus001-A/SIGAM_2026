<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Motivo y responsable de la baja de un equipo — antes solo se soft-deleteaba
 * sin pedir explicación ni dejar rastro visible de por qué.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table): void {
            $table->text('motivo_baja')->nullable()->after('notas');
            $table->foreignId('baja_por')->nullable()->after('motivo_baja')->constrained('usuarios')->nullOnDelete();
            $table->timestamp('baja_en')->nullable()->after('baja_por');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('baja_por');
            $table->dropColumn(['motivo_baja', 'baja_en']);
        });
    }
};
