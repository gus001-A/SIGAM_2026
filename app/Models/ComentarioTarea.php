<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comentario de supervisión sobre un movimiento del historial de una tarea.
 * Solo lo escriben supervisores y auditores (permiso `tareas.comentar`);
 * solo lo ven ellos mismos y los responsables activos de la tarea — nadie
 * más. Es de solo lectura: una vez publicado no se edita ni se borra.
 */
class ComentarioTarea extends Model
{
    use ConvierteMayusculas, HasFactory;

    protected $table = 'comentarios_tarea';

    const UPDATED_AT = null;

    protected $fillable = ['tarea_id', 'historial_estado_tarea_id', 'usuario_id', 'cuerpo'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    protected function camposMayusculas(): array
    {
        return ['cuerpo'];
    }

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class);
    }

    public function historialEstado(): BelongsTo
    {
        return $this->belongsTo(HistorialEstadoTarea::class, 'historial_estado_tarea_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
