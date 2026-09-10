<?php

namespace App\Models;

use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEquipo extends Model
{
    use HasFactory, TieneEstadoActivo;

    protected $table = 'tipos_equipo';

    protected $fillable = ['nombre', 'clave', 'descripcion', 'estado'];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'tipo_id');
    }
}
