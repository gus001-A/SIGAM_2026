<?php

namespace App\Support;

/**
 * Máquina de estados de una Tarea. Propuesta técnica SIGAM — anexo "TAREAS":
 * Pendiente → En proceso → Realizada, con Cancelada como salida desde
 * Pendiente o En proceso. Realizada y Cancelada son estados finales.
 */
class CicloTarea
{
    private const TRANSICIONES = [
        'pendiente' => ['en_proceso', 'cancelada'],
        'en_proceso' => ['realizada', 'cancelada'],
        'realizada' => [],
        'cancelada' => [],
    ];

    private const ETIQUETAS = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En proceso',
        'realizada' => 'Realizada',
        'cancelada' => 'Cancelada',
    ];

    public static function permite(string $origen, string $destino): bool
    {
        return in_array($destino, self::TRANSICIONES[$origen] ?? [], true);
    }

    /** @return list<string> */
    public static function siguientes(string $estado): array
    {
        return self::TRANSICIONES[$estado] ?? [];
    }

    public static function esFinal(string $estado): bool
    {
        return self::siguientes($estado) === [];
    }

    public static function etiqueta(string $estado): string
    {
        return self::ETIQUETAS[$estado] ?? $estado;
    }
}
