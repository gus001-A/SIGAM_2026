<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Formato / checklist configurable. Especificación v2.0 §5.16.
 */
class Formato extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, SoftDeletes, TieneEstadoActivo;

    protected $table = 'formatos';

    protected $fillable = ['nombre', 'descripcion', 'version', 'estado'];

    public function auditoriaModulo(): string
    {
        return 'formatos';
    }

    protected function camposMayusculas(): array
    {
        return ['nombre', 'descripcion'];
    }

    public function campos(): HasMany
    {
        return $this->hasMany(CampoFormato::class)->orderBy('orden');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaFormato::class);
    }
}
