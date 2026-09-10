<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Cálculo de fechas para planes de mantenimiento preventivo.
 * Propuesta SIGAM §7.2: semanal, mensual, bimestral, trimestral, semestral,
 * anual, por número de días o regla personalizada.
 */
class Frecuencia
{
    /** Suma un periodo a la fecha dada según el tipo y valor de frecuencia. */
    public static function siguiente(Carbon $desde, string $tipo, int $valor = 1): Carbon
    {
        $valor = max(1, $valor);
        $fecha = $desde->copy();

        return match ($tipo) {
            'dias' => $fecha->addDays($valor),
            'semanal' => $fecha->addWeeks($valor),
            'mensual' => $fecha->addMonthsNoOverflow($valor),
            'bimestral' => $fecha->addMonthsNoOverflow(2 * $valor),
            'trimestral' => $fecha->addMonthsNoOverflow(3 * $valor),
            'semestral' => $fecha->addMonthsNoOverflow(6 * $valor),
            'anual' => $fecha->addYearsNoOverflow($valor),
            default => $fecha->addMonthsNoOverflow($valor), // "personalizada" se resuelve en la app
        };
    }

    /**
     * Genera las próximas $cantidad fechas a partir de una fecha base.
     *
     * @return list<Carbon>
     */
    public static function proximas(Carbon $base, string $tipo, int $valor, int $cantidad): array
    {
        $fechas = [];
        $cursor = $base->copy();

        for ($i = 0; $i < $cantidad; $i++) {
            $cursor = self::siguiente($cursor, $tipo, $valor);
            $fechas[] = $cursor->copy();
        }

        return $fechas;
    }
}
