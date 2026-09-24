<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite que una solicitud/orden de mantenimiento apunte a una instalación
 * (ubicación) en vez de a un equipo específico — p. ej. "revisar el sistema
 * eléctrico del área de quirófanos". Exactamente uno de los dos se exige a
 * nivel de aplicación (ver GuardarSolicitudRequest/GuardarMantenimientoRequest).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['solicitudes_mantenimiento', 'mantenimientos'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['equipo_id']);
            });

            Schema::table($tabla, function (Blueprint $table) {
                $table->unsignedBigInteger('equipo_id')->nullable()->change();
                $table->foreignId('ubicacion_id')->nullable()->after('equipo_id')
                    ->constrained('ubicaciones')->cascadeOnDelete();
            });

            Schema::table($tabla, function (Blueprint $table) {
                $table->foreign('equipo_id')->references('id')->on('equipos')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['solicitudes_mantenimiento', 'mantenimientos'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['ubicacion_id']);
                $table->dropColumn('ubicacion_id');
                $table->dropForeign(['equipo_id']);
            });

            Schema::table($tabla, function (Blueprint $table) {
                $table->unsignedBigInteger('equipo_id')->nullable(false)->change();
            });

            Schema::table($tabla, function (Blueprint $table) {
                $table->foreign('equipo_id')->references('id')->on('equipos')->cascadeOnDelete();
            });
        }
    }
};
