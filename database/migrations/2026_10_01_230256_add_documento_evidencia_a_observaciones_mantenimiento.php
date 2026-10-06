<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La bitácora de una orden emparejaba su foto de evidencia por título +
 * cercanía de fecha (ver antiguo MantenimientoController::adjuntarEvidenciaABitacora()),
 * una heurística fallida: si un estado se visitaba más de una vez (p. ej. "realizado"
 * dos veces) el movimiento SIN foto terminaba mostrando la foto de un movimiento
 * distinto. Ahora que la evidencia es opcional esto se nota mucho más seguido.
 *
 * Se reemplaza por un enlace directo (documento_evidencia_id). Esta migración
 * rellena ese enlace para los movimientos que ya existían, usando la misma
 * heurística pero sin poder reutilizar un documento ya emparejado — así cada
 * foto real queda ligada a lo más a un movimiento, en vez de a varios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observaciones_mantenimiento', function (Blueprint $table) {
            $table->foreignId('documento_evidencia_id')->nullable()->after('cuerpo')
                ->constrained('documentos')->nullOnDelete();
        });

        $this->rellenarEnlacesExistentes();
    }

    public function down(): void
    {
        Schema::table('observaciones_mantenimiento', function (Blueprint $table) {
            $table->dropConstrainedForeignId('documento_evidencia_id');
        });
    }

    private function rellenarEnlacesExistentes(): void
    {
        $observaciones = DB::table('observaciones_mantenimiento')
            ->orderBy('mantenimiento_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'mantenimiento_id', 'cuerpo', 'created_at']);

        $pools = [];

        foreach ($observaciones as $obs) {
            $cuerpo = mb_strtoupper((string) $obs->cuerpo);

            if (preg_match('/^SE MOVIÓ LA ORDEN A «(.+?)»\./u', $cuerpo, $coincidencia)) {
                $titulo = mb_strtoupper("Evidencia: {$coincidencia[1]}");
            } elseif (str_starts_with($cuerpo, 'SE REPROGRAMÓ LA ORDEN:')) {
                $titulo = 'EVIDENCIA: REPROGRAMACIÓN';
            } else {
                continue;
            }

            if (! isset($pools[$obs->mantenimiento_id])) {
                $pools[$obs->mantenimiento_id] = DB::table('documentos as d')
                    ->join('documento_relacionado as dr', 'dr.documento_id', '=', 'd.id')
                    ->where('dr.relacionado_type', 'App\\Models\\Mantenimiento')
                    ->where('dr.relacionado_id', $obs->mantenimiento_id)
                    ->where('dr.rol', 'evidencia')
                    ->whereNull('d.deleted_at')
                    ->get(['d.id', 'd.titulo', 'd.created_at'])
                    ->map(fn ($d) => (array) $d)
                    ->all();
            }

            $candidatos = array_values(array_filter(
                $pools[$obs->mantenimiento_id],
                fn ($d) => $d['titulo'] === $titulo,
            ));
            if ($candidatos === []) {
                continue;
            }

            usort($candidatos, fn ($a, $b) => abs(strtotime((string) $a['created_at']) - strtotime((string) $obs->created_at))
                <=> abs(strtotime((string) $b['created_at']) - strtotime((string) $obs->created_at)));
            $elegido = $candidatos[0];

            DB::table('observaciones_mantenimiento')->where('id', $obs->id)
                ->update(['documento_evidencia_id' => $elegido['id']]);

            $pools[$obs->mantenimiento_id] = array_values(array_filter(
                $pools[$obs->mantenimiento_id],
                fn ($d) => $d['id'] !== $elegido['id'],
            ));
        }
    }
};
