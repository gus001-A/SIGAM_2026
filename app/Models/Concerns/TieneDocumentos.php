<?php

namespace App\Models\Concerns;

use App\Models\Documento;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Adjunta documentos polimórficos (equipos, sucursales, mantenimientos,
 * normas, proveedores). Especificación v2.0 §9 / §14.
 */
trait TieneDocumentos
{
    public function documentos(): MorphToMany
    {
        return $this->morphToMany(Documento::class, 'relacionado', 'documento_relacionado', 'relacionado_id', 'documento_id')
            ->withPivot(['rol'])
            ->withTimestamps();
    }
}
