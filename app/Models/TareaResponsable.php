<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TareaResponsable extends Model
{
    use ConvierteMayusculas, HasFactory;

    protected $table = 'tarea_responsables';

    public $timestamps = false;

    protected $fillable = [
        'tarea_id', 'usuario_id', 'asignado_por', 'es_principal', 'asignado_at', 'desasignado_at', 'notas',
    ];

    protected function camposMayusculas(): array
    {
        return ['notas'];
    }

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'asignado_at' => 'datetime',
            'desasignado_at' => 'datetime',
        ];
    }

    public function tarea(): BelongsTo
    {
        return $this->belongsTo(Tarea::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'asignado_por');
    }
}
