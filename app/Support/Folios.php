<?php

namespace App\Support;

use App\Models\Sucursal;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
     * Sugerencia de código de activo para un equipo nuevo, formato EQ-001,
     * EQ-002... Es solo una VISTA PREVIA (no reserva el consecutivo, para
     * no dejar huecos si el usuario abre el formulario y no lo guarda) — el
     * campo sigue siendo editable, así que un usuario puede aceptar esta
     * sugerencia, escribir otro código a mano, o llenarlo con el lector de
     * código de barras. La unicidad real la garantiza la validación normal
     * del formulario al guardar.
     */
    public static function previsualizarCodigoEquipo(): string
    {
        $consecutivo = DB::table('secuencias_folio')->where('llave', 'EQ')->value('consecutivo');

        if ($consecutivo === null) {
            $consecutivo = DB::table('equipos')->where('codigo_activo', 'like', 'EQ-%')->count();
        }

        return sprintf('EQ-%03d', $consecutivo + 1);
    }

    /**
     * Código automático de sucursal: 4 letras (del nombre) + consecutivo,
     * p. ej. "HOSP-01" (observaciones generales del cliente §9). El campo
     * sigue siendo editable — esto solo aplica cuando se deja en blanco.
     * Considera también las sucursales dadas de baja: `codigo` es único a
     * nivel de base de datos y el soft-delete no libera el valor.
     */
    public static function codigoSucursal(string $nombre, ?int $excluirId = null): string
    {
        $prefijo = self::prefijoLetras($nombre);
        $n = 1;

        do {
            $candidato = sprintf('%s-%02d', $prefijo, $n);
            $existe = Sucursal::withTrashed()
                ->where('codigo', $candidato)
                ->when($excluirId, fn ($q, $v) => $q->whereKeyNot($v))
                ->exists();
            $n++;
        } while ($existe);

        return $candidato;
    }

    /**
     * Código automático de ubicación: 4 letras (del nombre) + consecutivo,
     * único dentro de la sucursal (observaciones generales del cliente §10-11).
     */
    public static function codigoUbicacion(string $nombre, int $sucursalId, ?int $excluirId = null): string
    {
        $prefijo = self::prefijoLetras($nombre);
        $n = 1;

        do {
            $candidato = sprintf('%s-%02d', $prefijo, $n);
            $existe = Ubicacion::withTrashed()
                ->where('sucursal_id', $sucursalId)
                ->where('codigo', $candidato)
                ->when($excluirId, fn ($q, $v) => $q->whereKeyNot($v))
                ->exists();
            $n++;
        } while ($existe);

        return $candidato;
    }

    /** Primeras 4 letras del nombre, en MAYÚSCULAS, sin acentos ni espacios. */
    private static function prefijoLetras(string $nombre): string
    {
        $letras = Str::of($nombre)->ascii()->upper()->replaceMatches('/[^A-Z]/', '')->toString();

        return str_pad(substr($letras, 0, 4), 4, 'X');
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
