<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Generación de folios consecutivos por año. Especificación v2.0 §5.12
 * (cada mantenimiento conserva folio) y criterios de aceptación.
 *
 * Formato: PREFIJO-AAAA-NNNNN  (p.ej. SOL-2026-00042, MTO-2026-00042).
 */
class Folios
{
    /** Folio para una solicitud de mantenimiento. */
    public static function solicitud(): string
    {
        return self::siguiente('solicitudes_mantenimiento', 'SOL');
    }

    /** Folio para una orden de mantenimiento. */
    public static function mantenimiento(): string
    {
        return self::siguiente('mantenimientos', 'MTO');
    }

    /**
     * Calcula el siguiente folio disponible para la tabla y prefijo dados.
     * Se apoya en un bloqueo de la tabla de secuencias para evitar colisiones.
     */
    public static function siguiente(string $tabla, string $prefijo): string
    {
        $anio = now()->year;
        $llave = "{$prefijo}-{$anio}";

        return DB::transaction(function () use ($tabla, $prefijo, $anio, $llave) {
            $consecutivo = DB::table('secuencias_folio')
                ->lockForUpdate()
                ->where('llave', $llave)
                ->value('consecutivo');

            if ($consecutivo === null) {
                // Respaldo: arranca a partir de lo que ya exista en la tabla destino.
                $existentes = DB::table($tabla)
                    ->where('folio', 'like', "{$prefijo}-{$anio}-%")
                    ->count();
                $consecutivo = $existentes;
                DB::table('secuencias_folio')->insert([
                    'llave' => $llave,
                    'consecutivo' => $consecutivo,
                ]);
            }

            $consecutivo++;

            DB::table('secuencias_folio')
                ->where('llave', $llave)
                ->update(['consecutivo' => $consecutivo]);

            return sprintf('%s-%d-%05d', $prefijo, $anio, $consecutivo);
        });
    }
}
