<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proyecto extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, TieneEstadoActivo;

    protected $table = 'proyectos';

    protected $fillable = ['nombre', 'clave', 'descripcion', 'estado'];

    protected function camposMayusculas(): array
    {
        return ['nombre'];
    }

    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class);
    }
}
