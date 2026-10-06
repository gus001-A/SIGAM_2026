<?php

namespace App\Models\Concerns;

use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Registra automáticamente en la bitácora los cambios del modelo (§13).
 * Solo audita cuando hay un usuario autenticado (evita ruido de seeders/consola).
 * El módulo se deriva de la tabla salvo que el modelo declare `auditoriaModulo()`.
 */
trait EsAuditable
{
    public static function bootEsAuditable(): void
    {
        static::created(fn (Model $m) => $m->auditar('crear', [], $m->attributesToArray()));

        static::updated(function (Model $m): void {
            $cambios = $m->getChanges();
            unset($cambios['updated_at']);
            if ($cambios === []) {
                return;
            }
            $anteriores = collect($cambios)->mapWithKeys(fn ($v, $k) => [$k => $m->getOriginal($k)])->all();
            $m->auditar('actualizar', $anteriores, $cambios);
        });

        static::deleted(function (Model $m): void {
            $accion = method_exists($m, 'isForceDeleting') && $m->isForceDeleting() ? 'eliminar' : 'desactivar';
            $m->auditar($accion, $m->attributesToArray(), []);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $m) => $m->auditar('reactivar', [], $m->attributesToArray()));
        }
    }

    public function auditoriaModulo(): string
    {
        return Str::of($this->getTable())->replace('_', ' ')->__toString();
    }

    /**
     * @param  array<string, mixed>  $anteriores
     * @param  array<string, mixed>  $nuevos
     */
    protected function auditar(string $accion, array $anteriores, array $nuevos): void
    {
        if (Auth::guest()) {
            return;
        }

        Auditoria::registrar($accion, $this->auditoriaModulo(), $this, $anteriores, $nuevos);
    }

    /**
     * Bitácora de cambios del registro (altas, ediciones, bajas y reactivaciones)
     * con quién, cuándo y qué campos cambiaron. Para mostrar en la ficha de detalle.
     *
     * @return list<array<string, mixed>>
     */
    public function bitacoraCambios(int $limite = 30, array $movimientos = []): array
    {
        $ediciones = RegistroAuditoria::where('auditable_type', $this->getMorphClass())
            ->where('auditable_id', $this->getKey())
            ->whereIn('accion', ['crear', 'actualizar', 'desactivar', 'eliminar', 'reactivar'])
            ->with('usuario:id,nombre,apellidos')
            ->latest('created_at')
            ->limit($limite * 3)
            ->get()
            ->map(function (RegistroAuditoria $r): ?array {
                $anteriores = $r->valores_anteriores ?? [];
                $nuevos = $r->valores_nuevos ?? [];

                // Las ediciones se muestran solo si cambian datos reales: los cambios
                // de estado y de flujo (iniciado, completado, supervisado…) se ignoran.
                // Las fechas (campos *_at) no se muestran: la entrada ya trae su fecha.
                // Los campos *_por muestran el nombre de la persona, no su número.
                $campos = collect(array_keys($r->accion === 'actualizar' ? $nuevos : []))
                    ->reject(fn ($c) => in_array($c, self::CAMPOS_DE_FLUJO, true)
                        || in_array($c, ['updated_at', 'created_at'], true)
                        || str_ends_with((string) $c, '_at'))
                    ->map(fn ($c) => [
                        'campo' => str_replace('_', ' ', (string) $c),
                        'antes' => str_ends_with((string) $c, '_por')
                            ? self::nombreUsuario($anteriores[$c] ?? null)
                            : self::textoValor($anteriores[$c] ?? null),
                        'despues' => str_ends_with((string) $c, '_por')
                            ? self::nombreUsuario($nuevos[$c] ?? null)
                            : self::textoValor($nuevos[$c] ?? null),
                    ])
                    ->values()
                    ->all();

                if ($r->accion === 'actualizar' && $campos === []) {
                    return null;
                }

                return [
                    'id' => $r->id,
                    'accion' => $r->accion,
                    'etiqueta' => self::etiquetaAccion($r->accion),
                    'usuario' => $r->usuario?->nombre_completo ?? 'Sistema',
                    'fecha' => $r->created_at?->toISOString(),
                    'cambios' => $campos,
                ];
            })
            ->filter()
            ->values()
            ->all();

        // Movimientos de estado (tareas, órdenes) se muestran junto con las ediciones.
        return collect($ediciones)->concat($movimientos)->sortByDesc('fecha')->take($limite)->values()->all();
    }

    /** Campos que solo registran el avance del flujo (estados y fechas de etapa). No se muestran en la bitácora. */
    private const CAMPOS_DE_FLUJO = [
        'estado', 'estado_id', 'estado_origen', 'estado_destino',
        'autorizado_at', 'iniciado_at', 'iniciada_at', 'completado_at', 'realizada_at',
        'supervisado_at', 'cerrado_at', 'cancelada_at', 'cancelado_at',
        'supervisor_id', 'cerrado_por', 'cancelado_por',
    ];

    private static function etiquetaAccion(string $accion): string
    {
        return [
            'crear' => 'Creó el registro',
            'actualizar' => 'Editó',
            'desactivar' => 'Dio de baja',
            'eliminar' => 'Eliminó',
            'reactivar' => 'Reactivó',
        ][$accion] ?? ucfirst($accion);
    }

    /** Nombre de la persona a partir de su id (para campos *_por). */
    private static function nombreUsuario(mixed $id): ?string
    {
        if (blank($id)) {
            return null;
        }

        return Usuario::withTrashed()->find($id)?->nombre_completo ?? (string) $id;
    }

    private static function textoValor(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }
        if (is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?/', $valor)) {
            return Carbon::parse($valor)->format('d/m/Y H:i');
        }

        return is_scalar($valor) ? (string) $valor : json_encode($valor, JSON_UNESCAPED_UNICODE);
    }

    /**
     * "Sello" de trazabilidad para mostrar en pantalla: quién dio de alta el
     * registro y cuándo, y quién hizo el último cambio y cuándo (§13 — buenas
     * prácticas: alta/baja/modificación siempre a la vista, con usuario+fecha+hora).
     *
     * @return array{creado_por: ?string, creado_en: ?string, modificado_por: ?string, modificado_en: ?string}
     */
    public function selloAuditoria(): array
    {
        $base = RegistroAuditoria::where('auditable_type', $this->getMorphClass())
            ->where('auditable_id', $this->getKey())
            ->with('usuario:id,nombre,apellidos');

        $creado = (clone $base)->where('accion', 'crear')->oldest('created_at')->first();
        $modificado = (clone $base)->where('accion', 'actualizar')->latest('created_at')->first();
        $eliminado = (clone $base)->whereIn('accion', ['desactivar', 'eliminar'])->latest('created_at')->first();

        return [
            'creado_por' => $creado?->usuario?->nombre_completo,
            'creado_en' => $creado?->created_at?->toISOString(),
            'modificado_por' => $modificado?->usuario?->nombre_completo,
            'modificado_en' => $modificado?->created_at?->toISOString(),
            'eliminado_por' => $eliminado?->usuario?->nombre_completo,
            'eliminado_en' => $eliminado?->created_at?->toISOString(),
        ];
    }
}
