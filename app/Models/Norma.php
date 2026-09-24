<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneDocumentos;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Norma / procedimiento aplicable. Especificación v2.0 §5.14.
 */
class Norma extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, SoftDeletes, TieneDocumentos, TieneEstadoActivo;

    protected $table = 'normas';

    protected $fillable = [
        'codigo', 'nombre', 'version', 'descripcion', 'fecha_vigencia', 'fecha_revision', 'documento_id', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_vigencia' => 'date',
            'fecha_revision' => 'date',
        ];
    }

    public function auditoriaModulo(): string
    {
        return 'normas';
    }

    protected function camposMayusculas(): array
    {
        return ['codigo', 'nombre', 'descripcion'];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'equipo_norma');
    }

    public function planes(): HasMany
    {
        return $this->hasMany(PlanMantenimiento::class);
    }

    public function mantenimientos(): BelongsToMany
    {
        return $this->belongsToMany(Mantenimiento::class, 'mantenimiento_norma');
    }
}
