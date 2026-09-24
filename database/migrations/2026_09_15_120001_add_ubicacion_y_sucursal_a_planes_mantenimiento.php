<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mismo cambio que en solicitudes/mantenimientos: un plan preventivo puede
 * apuntar a una instalación en vez de a un equipo. A diferencia de esas dos
 * tablas, planes_mantenimiento no tenía sucursal_id propio (dependía de
 * equipo->sucursal_id) — se agrega aquí y se rellena para las filas ya
 * existentes, porque generarOrden() y los reportes por sucursal la necesitan
 * directo, sin pasar por la relación equipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->dropForeign(['equipo_id']);
        });

        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->unsignedBigInteger('equipo_id')->nullable()->change();
            $table->foreignId('ubicacion_id')->nullable()->after('equipo_id')
                ->constrained('ubicaciones')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->nullable()->after('ubicacion_id')
                ->constrained('sucursales')->cascadeOnDelete();
        });

        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->foreign('equipo_id')->references('id')->on('equipos')->cascadeOnDelete();
        });

        // Backfill portable (sin JOIN en UPDATE, que MySQL soporta pero SQLite no).
        DB::table('planes_mantenimiento')
            ->whereNotNull('equipo_id')
            ->orderBy('id')
            ->chunkById(200, function ($planes): void {
                foreach ($planes as $plan) {
                    $sucursalId = DB::table('equipos')->where('id', $plan->equipo_id)->value('sucursal_id');
                    if ($sucursalId) {
                        DB::table('planes_mantenimiento')->where('id', $plan->id)->update(['sucursal_id' => $sucursalId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->dropForeign(['ubicacion_id']);
            $table->dropColumn('ubicacion_id');
            $table->dropForeign(['sucursal_id']);
            $table->dropColumn('sucursal_id');
            $table->dropForeign(['equipo_id']);
        });

        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->unsignedBigInteger('equipo_id')->nullable(false)->change();
        });

        Schema::table('planes_mantenimiento', function (Blueprint $table) {
            $table->foreign('equipo_id')->references('id')->on('equipos')->cascadeOnDelete();
        });
    }
};
