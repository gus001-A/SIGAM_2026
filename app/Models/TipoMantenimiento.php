<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoMantenimiento extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, TieneEstadoActivo;

    protected $table = 'tipos_mantenimiento';

    protected $fillable = ['nombre', 'clave', 'categoria', 'descripcion', 'estado'];

    public function planes(): HasMany
    {
        return $this->hasMany(PlanMantenimiento::class, 'tipo_mantenimiento_id');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'tipo_id');
    }

    protected function camposMayusculas(): array
    {
        return ['nombre', 'descripcion'];
    }
}
