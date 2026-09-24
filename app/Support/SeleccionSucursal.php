<?php

namespace App\Support;

use App\Models\Sucursal;

/**
 * Resuelve qué sucursal usar como filtro en los módulos que "trabajan por
 * sucursal" (Equipos, Solicitudes, Órdenes, Planes, Calendario, Ubicaciones):
 * si viene algo en el query string se usa eso (y se recuerda en sesión para
 * los demás módulos); si no viene nada se usa lo último recordado; si nunca
 * se ha elegido nada se autoselecciona la primera sucursal activa (o
 * `$porDefecto`, cuando el módulo tiene su propio criterio). El valor
 * especial "todas" desactiva el filtro para ver el listado combinado.
 */
class SeleccionSucursal
{
    public const TODAS = 'todas';

    private const CLAVE_SESION = 'sigam.sucursal_seleccionada';

    public static function resolver(?string $valor, ?int $porDefecto = null): ?int
    {
        if ($valor === self::TODAS) {
            session([self::CLAVE_SESION => self::TODAS]);

            return null;
        }

        if (filled($valor)) {
            session([self::CLAVE_SESION => (int) $valor]);

            return (int) $valor;
        }

        $recordado = session(self::CLAVE_SESION);

        if ($recordado === self::TODAS) {
            return null;
        }

        if ($recordado && Sucursal::activos()->whereKey($recordado)->exists()) {
            return (int) $recordado;
        }

        $id = $porDefecto ?? Sucursal::activos()->orderBy('nombre')->value('id');
        session([self::CLAVE_SESION => $id]);

        return $id;
    }
}
