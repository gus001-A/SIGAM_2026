<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneDocumentos;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Solicitud de mantenimiento correctivo. Especificación v2.0 §5.10 / RF-043.
 */
class SolicitudMantenimiento extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, TieneDocumentos;

    protected $table = 'solicitudes_mantenimiento';

    protected $fillable = [
        'folio', 'equipo_id', 'ubicacion_id', 'sucursal_id', 'solicitado_por', 'prioridad_id', 'estado_id',
        'descripcion', 'fecha_requerida', 'solicitado_at', 'revisado_por', 'revisado_at', 'motivo_rechazo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_requerida' => 'date',
            'solicitado_at' => 'datetime',
            'revisado_at' => 'datetime',
        ];
    }

    public function auditoriaModulo(): string
    {
        return 'solicitudes';
    }

    protected function camposMayusculas(): array
    {
        return ['descripcion', 'motivo_rechazo'];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'solicitado_por');
    }

    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'revisado_por');
    }

    public function prioridad(): BelongsTo
    {
        return $this->belongsTo(Prioridad::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoMantenimiento::class, 'estado_id');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'solicitud_id');
    }
}
