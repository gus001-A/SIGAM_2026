<?php

namespace App\Support;

use App\Models\Material;
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
     * Sugerencia de código de activo para un equipo nuevo, con el distintivo
     * de su sucursal: CODIGO_SUCURSAL-EQ-001, p. ej. "CSA-01-EQ-001". Cada
     * sucursal lleva su propio consecutivo, de mínimo 3 dígitos pero sin tope
     * (…-EQ-999 → …-EQ-1000). Toma el número más alto ya usado con ese
     * prefijo en la sucursal (incluye equipos dados de baja, que siguen
     * ocupando su código) + 1.
     *
     * Es solo una VISTA PREVIA (no reserva el consecutivo) — el campo sigue
     * siendo editable. La unicidad real la garantiza la validación al guardar.
     *
     * @return array<int, string>  sucursal_id => código sugerido
     */
    public static function codigosSugeridosEquipo(): array
    {
        $codigosPorSucursal = DB::table('equipos')
            ->where('codigo_activo', 'like', '%-EQ-%')
            ->get(['sucursal_id', 'codigo_activo'])
            ->groupBy('sucursal_id');

        return Sucursal::withTrashed()->get(['id', 'codigo', 'nombre'])
            ->mapWithKeys(function (Sucursal $s) use ($codigosPorSucursal) {
                $prefijo = self::prefijoEquipo($s);
                $patron = '/^'.preg_quote($prefijo, '/').'-EQ-(\d{1,15})$/i';
                $maximo = 0;

                foreach ($codigosPorSucursal->get($s->id, []) as $e) {
                    if (preg_match($patron, $e->codigo_activo, $m)) {
                        $maximo = max($maximo, (int) $m[1]);
                    }
                }

                return [$s->id => sprintf('%s-EQ-%03d', $prefijo, $maximo + 1)];
            })
            ->all();
    }

    /**
     * Distintivo de la sucursal para el código de sus equipos: su `codigo`
     * (solo letras, números y guiones, en MAYÚSCULAS); si no tiene, las 4
     * letras de su nombre.
     */
    private static function prefijoEquipo(Sucursal $sucursal): string
    {
        $codigo = Str::of((string) $sucursal->codigo)->ascii()->upper()
            ->replaceMatches('/[^A-Z0-9-]+/', '-')->trim('-')->toString();

        return $codigo !== '' ? $codigo : self::prefijoLetras($sucursal->nombre);
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

    /**
     * Código automático de material/refacción: 4 letras (del nombre) +
     * consecutivo, p. ej. "ACEI-01". El campo sigue siendo editable — esto
     * solo aplica cuando se deja en blanco.
     */
    public static function codigoMaterial(string $nombre, ?int $excluirId = null): string
    {
        $prefijo = self::prefijoLetras($nombre);
        $n = 1;

        do {
            $candidato = sprintf('%s-%02d', $prefijo, $n);
            $existe = Material::where('codigo', $candidato)
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
