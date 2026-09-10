<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'asignaciones_mantenimiento';

    public $timestamps = false;

    protected $fillable = [
        'mantenimiento_id', 'tecnico_id', 'asignado_por', 'es_principal', 'asignado_at', 'desasignado_at', 'notas',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'asignado_at' => 'datetime',
            'desasignado_at' => 'datetime',
        ];
    }

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'tecnico_id');
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'asignado_por');
    }
}
