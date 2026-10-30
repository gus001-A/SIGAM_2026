<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las tareas pasan a tener la misma cadena final que las órdenes:
 *   Asignada → En proceso → Completada → Autorizada → Supervisada → Cerrada
 * (+ Reprogramada / Cancelada en el camino).
 *
 * Los nombres guardados en `tareas.estado` cambian:
 *   pendiente  → asignada
 *   realizada  → completada
 * (en_proceso y cancelada no cambian). Se agregan las columnas para los
 * nuevos pasos, igual que ya existen en `mantenimientos` (autorizado_por/
 * autorizado_at, supervisor_id/supervisado_at, cerrado_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->timestamp('autorizada_at')->nullable()->after('realizada_at');
            $table->foreignId('autorizado_por')->nullable()->after('autorizada_at')
                ->constrained('usuarios')->nullOnDelete();
            $table->timestamp('supervisada_at')->nullable()->after('autorizado_por');
            $table->foreignId('supervisor_id')->nullable()->after('supervisada_at')
                ->constrained('usuarios')->nullOnDelete();
            $table->timestamp('cerrada_at')->nullable()->after('supervisor_id');
            $table->timestamp('reprogramada_at')->nullable()->after('cerrada_at');
        });

        DB::table('tareas')->where('estado', 'pendiente')->update(['estado' => 'asignada']);
        DB::table('tareas')->where('estado', 'realizada')->update(['estado' => 'completada']);
        // El DEFAULT de la columna se queda en 'pendiente' a nivel de BD (cambiarlo
        // requiere doctrine/dbal, que no está instalado), pero no importa: el
        // controlador siempre manda `estado` de forma explícita al crear una tarea.
    }

    public function down(): void
    {
        DB::table('tareas')->where('estado', 'asignada')->update(['estado' => 'pendiente']);
        DB::table('tareas')->where('estado', 'completada')->update(['estado' => 'realizada']);
        // Las tareas que ya avanzaron a autorizada/supervisada/cerrada/reprogramada
        // no tienen un equivalente anterior: se dejan en "realizada" para no perder
        // el hecho de que el trabajo ya se había terminado.
        DB::table('tareas')->whereIn('estado', ['autorizada', 'supervisada', 'cerrada', 'reprogramada'])
            ->update(['estado' => 'realizada']);

        Schema::table('tareas', function (Blueprint $table) {
            $table->dropColumn(['autorizada_at', 'autorizado_por', 'supervisada_at', 'supervisor_id', 'cerrada_at', 'reprogramada_at']);
        });
    }
};
