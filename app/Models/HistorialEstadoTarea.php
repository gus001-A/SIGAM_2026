<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Historial de cambios de estado de una tarea. Espejo de
 * `HistorialEstadoMantenimiento`, con estados de texto libre.
 */
class HistorialEstadoTarea extends Model
{
    use ConvierteMayusculas, HasFactory;

    protected $table = 'historial_estados_tarea';

    public $timestamps = false;

    protected $fillable = [
        'tarea_id', 'estado_origen', 'estado_destino', 'cambiado_por', 'nota', 'documento_evidencia_id', 'cambiado_at',
    ];

    protected function camposMayusculas(): array
    {
        return ['nota'];
    }

    protected function casts(): array
    {
        return ['cambiado_at' => 'datetime'];
    }

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class);
    }

    public function cambiadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cambiado_por');
    }

    public function evidencias(): MorphToMany
    {
        return $this->morphToMany(Documento::class, 'movimiento', 'evidencias_movimiento', 'movimiento_id', 'documento_id')
            ->withTimestamps();
    }
}
