<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialUbicacionEquipo extends Model
{
    use ConvierteMayusculas, HasFactory;

    protected $table = 'historial_ubicacion_equipo';

    public $timestamps = false;

    protected $fillable = [
        'equipo_id', 'ubicacion_origen_id', 'ubicacion_destino_id', 'cambiado_por', 'motivo', 'cambiado_at',
    ];

    protected function camposMayusculas(): array
    {
        return ['motivo'];
    }

    protected function casts(): array
    {
        return ['cambiado_at' => 'datetime'];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function ubicacionOrigen(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_origen_id');
    }

    public function ubicacionDestino(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_destino_id');
    }

    public function cambiadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cambiado_por');
    }
}
