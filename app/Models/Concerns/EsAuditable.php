<?php

namespace App\Models\Concerns;

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
}
