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

    /**
     * Quién dio de alta cada uno de los registros dados, en UNA sola consulta
     * (evita hacer N+1 llamando `selloAuditoria()` fila por fila en un
     * listado paginado). Pensado para pintar "Registrado por / fecha" en las
     * columnas de las tablas — solo el alta, no la última modificación.
     *
     * @param  class-string<Model>  $modelo
     * @param  iterable<int|string>  $ids
     * @return array<int|string, array{usuario: ?string, fecha: ?string}>
     */
    public static function creadoPorMasivo(string $modelo, iterable $ids): array
    {
        $ids = collect($ids)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return [];
        }

        return RegistroAuditoria::query()
            ->where('auditable_type', (new $modelo)->getMorphClass())
            ->whereIn('auditable_id', $ids)
            ->where('accion', 'crear')
            ->with('usuario:id,nombre,apellidos')
            ->oldest('created_at')
            ->get()
            ->unique('auditable_id')
            ->keyBy('auditable_id')
            ->map(fn (RegistroAuditoria $r) => [
                'usuario' => $r->usuario?->nombre_completo,
                'fecha' => $r->created_at?->toISOString(),
            ])
            ->all();
    }

    /**
     * IDs de los registros de `$modelo` cuya alta fue hecha por un usuario
     * cuyo nombre coincide con `$termino` — para el filtro "Registrado por"
     * de los listados (el nombre no vive en la propia tabla, solo en la
     * bitácora, así que no se puede filtrar con un `where` normal).
     *
     * @param  class-string<Model>  $modelo
     * @return array<int|string>
     */
    public static function idsCreadosPor(string $modelo, string $termino): array
    {
        return RegistroAuditoria::query()
            ->where('auditable_type', (new $modelo)->getMorphClass())
            ->where('accion', 'crear')
            ->whereHas('usuario', fn ($q) => $q
                ->where('nombre', 'like', "%{$termino}%")
                ->orWhere('apellidos', 'like', "%{$termino}%"))
            ->pluck('auditable_id')
            ->all();
    }
}
