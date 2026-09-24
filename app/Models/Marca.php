<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marca extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, TieneEstadoActivo;

    protected $table = 'marcas';

    protected $fillable = ['nombre', 'descripcion', 'estado'];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    protected function camposMayusculas(): array
    {
        return ['nombre', 'descripcion'];
    }
}
