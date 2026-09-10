<?php

namespace App\Support;

use App\Models\Notificacion;
use App\Models\Usuario;

/**
 * Emisión de notificaciones in-app del dominio SIGAM (§12).
 * Nunca lanza: una notificación fallida no debe tumbar la operación.
 */
class Notificaciones
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public static function crear(Usuario|int $usuario, string $tipo, string $titulo, ?string $cuerpo = null, array $datos = []): void
    {
        try {
            $usuarioId = $usuario instanceof Usuario ? $usuario->getKey() : $usuario;

            // Evita duplicar una notificación sin leer del mismo evento.
            $yaExiste = Notificacion::query()
                ->where('usuario_id', $usuarioId)
                ->where('tipo', $tipo)
                ->whereNull('leida_at')
                ->when(isset($datos['ref']), fn ($q) => $q->where('datos->ref', $datos['ref']))
                ->exists();

            if ($yaExiste) {
                return;
            }

            Notificacion::create([
                'usuario_id' => $usuarioId,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'cuerpo' => $cuerpo,
                'datos' => $datos ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Notifica a todos los usuarios activos que tengan alguno de los roles dados.
     *
     * @param  list<string>  $roles
     * @param  array<string, mixed>  $datos
     */
    public static function paraRoles(array $roles, string $tipo, string $titulo, ?string $cuerpo = null, array $datos = [], ?int $exceptoUsuarioId = null): void
    {
        Usuario::query()
            ->where('estado', 'activo')
            ->when($exceptoUsuarioId, fn ($q) => $q->whereKeyNot($exceptoUsuarioId))
            ->role($roles)
            ->pluck('id')
            ->each(fn ($id) => self::crear($id, $tipo, $titulo, $cuerpo, $datos));
    }
}
