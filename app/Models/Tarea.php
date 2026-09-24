<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneDocumentos;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tarea de seguimiento general (administrativa, no ligada a un equipo o
 * ubicación). Propuesta técnica SIGAM — anexo "TAREAS".
 */
class Tarea extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, SoftDeletes, TieneDocumentos;

    protected $table = 'tareas';

    protected $fillable = [
        'titulo', 'descripcion', 'fecha_limite', 'prioridad_id', 'estado', 'nota_avance', 'nota_cierre', 'nota_cancelacion',
        'costo', 'iniciada_at', 'realizada_at', 'cancelada_at', 'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_limite' => 'date',
            'costo' => 'decimal:2',
            'iniciada_at' => 'datetime',
            'realizada_at' => 'datetime',
            'cancelada_at' => 'datetime',
        ];
    }

    public function auditoriaModulo(): string
    {
        return 'tareas';
    }

    protected function camposMayusculas(): array
    {
        return ['titulo', 'descripcion', 'nota_avance', 'nota_cierre', 'nota_cancelacion'];
    }

    public function prioridad(): BelongsTo
    {
        return $this->belongsTo(Prioridad::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    /** Responsables activos e históricos (pivote con historial de asignación). */
    public function responsables(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'tarea_responsables', 'tarea_id', 'usuario_id')
            ->withPivot(['es_principal', 'asignado_por', 'asignado_at', 'desasignado_at', 'notas']);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(TareaResponsable::class);
    }

    public function historialEstados(): HasMany
    {
        return $this->hasMany(HistorialEstadoTarea::class)->orderBy('cambiado_at');
    }
}
