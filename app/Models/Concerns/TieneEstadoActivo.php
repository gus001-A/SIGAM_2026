<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Manejo uniforme de la columna `estado` (activo | inactivo) que usan los
 * catálogos y entidades operativas de SIGAM (Especificación v2.0 §4/§5.13).
 */
trait TieneEstadoActivo
{
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where($this->getTable().'.estado', 'activo');
    }

    public function scopeInactivos(Builder $query): Builder
    {
        return $query->where($this->getTable().'.estado', 'inactivo');
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function activar(): bool
    {
        return $this->forceFill(['estado' => 'activo'])->save();
    }

    public function desactivar(): bool
    {
        return $this->forceFill(['estado' => 'inactivo'])->save();
    }
}
