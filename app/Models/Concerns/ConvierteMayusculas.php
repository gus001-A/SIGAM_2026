<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Uniforma a MAYÚSCULAS los campos de texto libre que el modelo declare en
 * `camposMayusculas()`, para evitar registros con mayúsculas y minúsculas
 * mezcladas (observaciones generales del cliente §4). Deliberadamente NO
 * es un middleware global: cada modelo elige sus campos para no tocar
 * correos, contraseñas, claves de catálogo ni otros valores que la lógica
 * del sistema compara de forma exacta (p. ej. `clave`, `categoria`, `estado`).
 */
trait ConvierteMayusculas
{
    public static function bootConvierteMayusculas(): void
    {
        static::saving(function (Model $modelo): void {
            foreach ($modelo->camposMayusculas() as $campo) {
                if (is_string($modelo->{$campo}) && $modelo->{$campo} !== '') {
                    $modelo->{$campo} = mb_strtoupper($modelo->{$campo}, 'UTF-8');
                }
            }
        });
    }

    /** @return list<string> Campos de texto libre a normalizar en mayúsculas. */
    protected function camposMayusculas(): array
    {
        return [];
    }

    /**
     * Sube a MAYÚSCULAS los mismos campos que `camposMayusculas()`, mirados
     * desde afuera del modelo. Se usa ANTES de validar `unique:` (p. ej.
     * `nombre`/`codigo`) para que la comparación no se rompa por diferencia
     * de caja entre lo que la persona escribió y lo que ya está guardado.
     *
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public static function normalizarMayusculas(array $datos): array
    {
        $modelo = new static;
        foreach ($modelo->camposMayusculas() as $campo) {
            if (isset($datos[$campo]) && is_string($datos[$campo]) && $datos[$campo] !== '') {
                $datos[$campo] = mb_strtoupper($datos[$campo], 'UTF-8');
            }
        }

        return $datos;
    }
}
