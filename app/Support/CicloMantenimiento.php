<?php

namespace App\Support;

use App\Models\EstadoMantenimiento;
use App\Models\Mantenimiento;
use Illuminate\Support\Carbon;

/**
 * Máquina de estados de una orden de mantenimiento.
 * Especificación v2.0 §7 + Propuesta SIGAM §7.3.
 */
class CicloMantenimiento
{
    /**
     * Transiciones permitidas: estado actual => [estados destino].
     *
     * @var array<string, list<string>>
     */
    public const TRANSICIONES = [
        'solicitado' => ['autorizado', 'cancelado'],
        'autorizado' => ['asignado', 'cancelado', 'reprogramado'],
        'asignado' => ['en_proceso', 'reprogramado', 'cancelado'],
        'en_proceso' => ['en_espera_refaccion', 'fuera_de_servicio', 'realizado', 'reprogramado'],
        'en_espera_refaccion' => ['en_proceso', 'reprogramado', 'cancelado'],
        'fuera_de_servicio' => ['en_proceso', 'reprogramado'],
        'realizado' => ['supervisado', 'en_proceso'],
        'supervisado' => ['cerrado', 'en_proceso'],
        'reprogramado' => ['asignado', 'en_proceso', 'cancelado'],
        'cerrado' => [],
        'cancelado' => [],
    ];

    /** Marca de tiempo que se rellena al entrar a cada estado. */
    private const SELLOS = [
        'autorizado' => 'autorizado_at',
        'en_proceso' => 'iniciado_at',
        'realizado' => 'completado_at',
        'supervisado' => 'supervisado_at',
        'cerrado' => 'cerrado_at',
    ];

    /** @return list<string> */
    public static function siguientes(string $claveActual): array
    {
        return self::TRANSICIONES[$claveActual] ?? [];
    }

    public static function permite(string $desde, string $hacia): bool
    {
        return in_array($hacia, self::siguientes($desde), true);
    }

    /**
     * Aplica los efectos colaterales de entrar a un estado sobre la orden
     * (sin guardar). Devuelve la lista de motivos por los que NO se puede cerrar.
     *
     * @return list<string>
     */
    public static function validarCierre(Mantenimiento $mantenimiento): array
    {
        $faltantes = [];

        if (blank($mantenimiento->diagnostico)) {
            $faltantes[] = 'Falta el diagnóstico.';
        }
        if (blank($mantenimiento->descripcion_trabajo)) {
            $faltantes[] = 'Faltan las actividades realizadas.';
        }
        if (blank($mantenimiento->completado_at)) {
            $faltantes[] = 'La orden no ha sido marcada como realizada.';
        }

        return $faltantes;
    }

    public static function aplicarSello(Mantenimiento $mantenimiento, string $claveDestino): void
    {
        $columna = self::SELLOS[$claveDestino] ?? null;

        if ($columna && blank($mantenimiento->{$columna})) {
            $mantenimiento->{$columna} = Carbon::now();
        }
    }

    public static function estado(string $clave): EstadoMantenimiento
    {
        return EstadoMantenimiento::where('clave', $clave)->firstOrFail();
    }
}
