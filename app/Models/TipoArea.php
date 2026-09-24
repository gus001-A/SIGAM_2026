<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo de área hospitalaria por criticidad de limpieza (observaciones
 * generales del cliente §18): crítica, semi-crítica, no crítica — cada una
 * con su propia periodicidad de limpieza en días.
 */
class TipoArea extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, TieneEstadoActivo;

    protected $table = 'tipos_area';

    protected $fillable = ['nombre', 'clave', 'descripcion', 'dias_limpieza', 'estado'];

    protected function casts(): array
    {
        return ['dias_limpieza' => 'integer'];
    }

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(Ubicacion::class, 'tipo_area_id');
    }

    protected function camposMayusculas(): array
    {
        return ['nombre', 'descripcion'];
    }
}
