<?php

namespace App\Support;

use App\Models\RegistroAuditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Punto único de escritura de la bitácora de auditoría (§13).
 * Nunca lanza: un fallo al auditar no debe tumbar la operación de negocio.
 */
class Auditoria
{
    /** Claves que jamás se guardan en el diff. */
    private const OCULTAS = ['password', 'remember_token', 'password_confirmation', 'two_factor_secret', 'two_factor_recovery_codes'];

    /**
     * @param  array<string, mixed>  $anteriores
     * @param  array<string, mixed>  $nuevos
     * @param  array<string, mixed>  $metadatos
     */
    public static function registrar(
        string $accion,
        string $modulo,
        ?Model $auditable = null,
        array $anteriores = [],
        array $nuevos = [],
        array $metadatos = [],
        ?Model $usuario = null,
    ): void {
        try {
            $usuario ??= Auth::user();
            $request = request();

            RegistroAuditoria::create([
                'usuario_id' => $usuario?->getKey(),
                'accion' => $accion,
                'modulo' => $modulo,
                'auditable_type' => $auditable ? $auditable->getMorphClass() : null,
                'auditable_id' => $auditable?->getKey(),
                'sucursal_id' => $usuario->sucursal_id ?? null,
                'ip' => $request?->ip(),
                'navegador' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
                'valores_anteriores' => self::limpiar($anteriores) ?: null,
                'valores_nuevos' => self::limpiar($nuevos) ?: null,
                'metadatos' => $metadatos ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  array<string, mixed>  $valores
     * @return array<string, mixed>
     */
    private static function limpiar(array $valores): array
    {
        return collect($valores)
            ->except(self::OCULTAS)
            ->reject(fn ($v) => in_array($v, [null, ''], true))
            ->all();
    }
}
