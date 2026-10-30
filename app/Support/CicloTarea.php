<?php

namespace App\Support;

/**
 * Máquina de estados de una Tarea. Misma cadena final que una orden de
 * mantenimiento (`CicloMantenimiento`):
 *   Asignada → En proceso → Completada → Autorizada → Supervisada → Cerrada
 * con Reprogramada/Cancelada disponibles en el camino. Si el supervisor no
 * está de acuerdo al supervisar, regresa a Autorizada — no se borra nada de
 * lo ya registrado, solo se agrega una nueva vuelta en el historial.
 */
class CicloTarea
{
    private const TRANSICIONES = [
        'asignada' => ['en_proceso', 'reprogramada', 'cancelada'],
        'en_proceso' => ['completada', 'reprogramada', 'cancelada'],
        'completada' => ['autorizada'],
        'autorizada' => ['supervisada', 'reprogramada'],
        'supervisada' => ['cerrada', 'autorizada'],
        'reprogramada' => ['asignada', 'en_proceso', 'cancelada'],
        'cerrada' => [],
        'cancelada' => [],
    ];

    private const ETIQUETAS = [
        'asignada' => 'Asignada',
        'en_proceso' => 'En proceso',
        'completada' => 'Completada',
        'autorizada' => 'Autorizada',
        'supervisada' => 'Supervisada',
        'cerrada' => 'Cerrada',
        'reprogramada' => 'Reprogramada',
        'cancelada' => 'Cancelada',
    ];

    /**
     * Permiso que exige cada paso. Autorizar es del supervisor; supervisar (y
     * regresar de Supervisada a Autorizada) y reprogramar son del auditor.
     */
    public static function permisoPara(string $origen, string $destino): string
    {
        return match (true) {
            $destino === 'autorizada' && $origen === 'supervisada' => 'tareas.supervisar',
            $destino === 'autorizada' => 'tareas.autorizar',
            $destino === 'supervisada' => 'tareas.supervisar',
            $destino === 'reprogramada' => 'tareas.reprogramar',
            $destino === 'cerrada' => 'tareas.cerrar',
            default => 'tareas.editar',
        };
    }

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
