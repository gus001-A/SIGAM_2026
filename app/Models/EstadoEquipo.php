<?php

namespace App\Models;

use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoEquipo extends Model
{
    use HasFactory, TieneEstadoActivo;

    protected $table = 'estados_equipo';

    protected $fillable = ['nombre', 'clave', 'descripcion', 'color', 'es_operativo', 'estado'];

    protected function casts(): array
    {
        return ['es_operativo' => 'boolean'];
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'estado_id');
    }
}
