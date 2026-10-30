<?php

use App\Models\EstadoMantenimiento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rediseño del flujo de órdenes y tareas (pedido del usuario, oct. 2026):
 * ambos ahora comparten la misma cadena de revisión final:
 *   ... → Completada → [En espera de refacción → Reanudar] → Autorizada → Supervisada → Cerrada
 * con Reprogramada/Cancelada disponibles en el camino.
 *
 * Esta migración agrega el nuevo paso "Autorizada" de la orden — un chequeo
 * DISTINTO del "Autorizado" que ya existe (ese es el momento en que se
 * autoriza la SOLICITUD y nace la orden, antes de asignar técnico; no se
 * toca). El nuevo "Autorizada" ocurre después de "Completada" (clave
 * `realizado`) y antes de "Supervisada": alguien revisa el trabajo ya
 * terminado antes de que el supervisor lo supervise y cierre.
 *
 * También agrega `autorizada_at`/`autorizada_por` a `mantenimientos` (columnas
 * nuevas, no reutiliza `autorizado_at`/`autorizado_por` porque esas ya
 * registran la autorización de la solicitud original).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->timestamp('autorizada_at')->nullable()->after('autorizado_at');
            $table->foreignId('autorizada_por')->nullable()->after('autorizada_at')
                ->constrained('usuarios')->nullOnDelete();
        });

        EstadoMantenimiento::firstOrCreate(
            ['clave' => 'autorizada'],
            ['nombre' => 'Autorizada', 'orden' => 55, 'es_terminal' => false, 'es_abierto' => true, 'estado' => 'activo'],
        );

        // Los estados que ya existían se quedan con la misma `clave` (nada que
        // dependa de ella se rompe), pero su nombre visible cambia.
        $renombres = [
            'asignado' => 'Asignada',
            'realizado' => 'Completada',
            'supervisado' => 'Supervisada',
            'cerrado' => 'Cerrada',
            'reprogramado' => 'Reprogramada',
            'cancelado' => 'Cancelada',
        ];
        foreach ($renombres as $clave => $nombre) {
            DB::table('estados_mantenimiento')->where('clave', $clave)->update(['nombre' => $nombre]);
        }
    }

    public function down(): void
    {
        $nombresOriginales = [
            'asignado' => 'Asignado',
            'realizado' => 'Realizado',
            'supervisado' => 'Supervisado',
            'cerrado' => 'Cerrado',
            'reprogramado' => 'Reprogramado',
            'cancelado' => 'Cancelado',
        ];
        foreach ($nombresOriginales as $clave => $nombre) {
            DB::table('estados_mantenimiento')->where('clave', $clave)->update(['nombre' => $nombre]);
        }

        EstadoMantenimiento::where('clave', 'autorizada')->delete();

        Schema::table('mantenimientos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('autorizada_por');
            $table->dropColumn('autorizada_at');
        });
    }
};
