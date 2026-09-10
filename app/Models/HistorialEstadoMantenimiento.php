<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialEstadoMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'historial_estados_mantenimiento';

    public $timestamps = false;

    protected $fillable = [
        'mantenimiento_id', 'estado_origen_id', 'estado_destino_id', 'cambiado_por', 'nota', 'cambiado_at',
    ];

    protected function casts(): array
    {
        return ['cambiado_at' => 'datetime'];
    }

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function estadoOrigen(): BelongsTo
    {
        return $this->belongsTo(EstadoMantenimiento::class, 'estado_origen_id');
    }

    public function estadoDestino(): BelongsTo
    {
        return $this->belongsTo(EstadoMantenimiento::class, 'estado_destino_id');
    }

    public function cambiadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cambiado_por');
    }
}
