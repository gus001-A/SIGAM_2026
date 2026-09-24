<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo de limpieza hospitalaria (observaciones generales del cliente §19):
 * rutinaria, terminal, exhaustiva — cada una con su propia frecuencia.
 */
class TipoLimpieza extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, TieneEstadoActivo;

    protected $table = 'tipos_limpieza';

    protected $fillable = ['nombre', 'clave', 'frecuencia', 'descripcion', 'estado'];

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(Ubicacion::class, 'tipo_limpieza_id');
    }

    protected function camposMayusculas(): array
    {
        return ['nombre', 'frecuencia', 'descripcion'];
    }
}
