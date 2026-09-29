<?php

namespace App\Support;

use App\Mail\AvisoAsignacionMail;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Support\Facades\Mail;

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
     * Aviso por correo de una asignación (tarea/orden). Deliberadamente
     * separado de `crear()`: solo se llama junto a las notificaciones de
     * "te asignaron X", no para cada tipo de notificación in-app.
     */
    public static function correo(Usuario|int $usuario, string $titulo, string $cuerpo, string $url): void
    {
        try {
            $usuario = $usuario instanceof Usuario ? $usuario : Usuario::find($usuario);

            if (! $usuario?->email) {
                return;
            }

            Mail::to($usuario->email)->send(new AvisoAsignacionMail($titulo, $cuerpo, $url));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Notifica a todos los usuarios activos que tengan alguno de los roles dados.
     * `$conCorreo` además envía el aviso por correo a cada uno (usa
     * `$datos['url']` como enlace del botón del correo).
     *
     * @param  list<string>  $roles
     * @param  array<string, mixed>  $datos
     */
    public static function paraRoles(array $roles, string $tipo, string $titulo, ?string $cuerpo = null, array $datos = [], ?int $exceptoUsuarioId = null, bool $conCorreo = false): void
    {
        Usuario::query()
            ->where('estado', 'activo')
            ->when($exceptoUsuarioId, fn ($q) => $q->whereKeyNot($exceptoUsuarioId))
            ->role($roles)
            ->get(['id', 'email'])
            ->each(function (Usuario $usuario) use ($tipo, $titulo, $cuerpo, $datos, $conCorreo): void {
                self::crear($usuario, $tipo, $titulo, $cuerpo, $datos);
                if ($conCorreo) {
                    self::correo($usuario, $titulo, $cuerpo ?? '', $datos['url'] ?? '');
                }
            });
    }
}
