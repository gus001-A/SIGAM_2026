<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoUbicacion extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, TieneEstadoActivo;

    protected $table = 'tipos_ubicacion';

    protected $fillable = ['nombre', 'clave', 'descripcion', 'estado'];

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(Ubicacion::class, 'tipo_id');
    }

    protected function camposMayusculas(): array
    {
        return ['nombre', 'descripcion'];
    }
}
