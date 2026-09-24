<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prioridad extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, TieneEstadoActivo;

    protected $table = 'prioridades';

    protected $fillable = ['nombre', 'clave', 'nivel', 'minutos_respuesta', 'color', 'estado'];

    protected function casts(): array
    {
        return [
            'nivel' => 'integer',
            'minutos_respuesta' => 'integer',
        ];
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class);
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(SolicitudMantenimiento::class);
    }

    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class);
    }

    protected function camposMayusculas(): array
    {
        return ['nombre'];
    }
}
