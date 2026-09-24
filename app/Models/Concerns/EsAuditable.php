<?php

namespace App\Models\Concerns;

use App\Models\RegistroAuditoria;
use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Model;
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

        return [
            'creado_por' => $creado?->usuario?->nombre_completo,
            'creado_en' => $creado?->created_at?->toISOString(),
            'modificado_por' => $modificado?->usuario?->nombre_completo,
            'modificado_en' => $modificado?->created_at?->toISOString(),
        ];
    }
}
