<?php

namespace App\Support;

use App\Models\Sucursal;
use App\Models\Usuario;

/**
 * Resuelve qué sucursal usar como filtro en los módulos que "trabajan por
 * sucursal" (Equipos, Solicitudes, Órdenes, Planes, Calendario, Ubicaciones):
 * si viene algo en el query string se usa eso (y se recuerda en sesión para
 * los demás módulos); si no viene nada se usa lo último recordado; si nunca
 * se ha elegido nada se autoselecciona la primera sucursal activa (o
 * `$porDefecto`, cuando el módulo tiene su propio criterio). El valor
 * especial "todas" desactiva el filtro para ver el listado combinado.
 *
 * Un usuario que NO es superadministrador queda acotado a sus propias
 * sucursales asignadas: no puede elegir "todas", no puede pedir el id de una
 * sucursal ajena (se ignora si lo intenta por la URL), y si no tiene ninguna
 * sucursal asignada no ve ninguna.
 */
class SeleccionSucursal
{
    public const TODAS = 'todas';

    private const CLAVE_SESION = 'sigam.sucursal_seleccionada';

    public static function resolver(?string $valor, Usuario $usuario, ?int $porDefecto = null): ?int
    {
        if ($usuario->puedeVerTodasLasSucursales()) {
            return self::resolverSinRestriccion($valor, $porDefecto);
        }

        $permitidos = $usuario->sucursalIdsPermitidos();

        if ($permitidos === []) {
            // Sin sucursales asignadas: no debe ver ninguna (nunca "todas").
            return 0;
        }

        if ($valor === self::TODAS) {
            $valor = null; // un usuario acotado no puede pedir "todas".
        }

        if (filled($valor) && in_array((int) $valor, $permitidos, true)) {
            session([self::CLAVE_SESION => (int) $valor]);

            return (int) $valor;
        }

        $recordado = session(self::CLAVE_SESION);
        if (is_int($recordado) && in_array($recordado, $permitidos, true)) {
            return $recordado;
        }

        $id = in_array($porDefecto, $permitidos, true) ? $porDefecto : $permitidos[0];
        session([self::CLAVE_SESION => $id]);

        return $id;
    }

    private static function resolverSinRestriccion(?string $valor, ?int $porDefecto): ?int
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
