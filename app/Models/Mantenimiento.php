<?php

namespace App\Models;

use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneDocumentos;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Orden de mantenimiento (preventivo / correctivo / urgente). Especificación v2.0 §5.12.
 * Estado y prioridad son independientes (regla de negocio §7).
 */
class Mantenimiento extends Model
{
    use EsAuditable, HasFactory, SoftDeletes, TieneDocumentos;

    protected $table = 'mantenimientos';

    protected $fillable = [
        'folio', 'solicitud_id', 'plan_id', 'equipo_id', 'sucursal_id', 'tipo_id', 'prioridad_id', 'estado_id',
        'programado_inicio', 'programado_fin', 'autorizado_at', 'iniciado_at', 'completado_at', 'supervisado_at', 'cerrado_at',
        'problema_reportado', 'diagnostico', 'descripcion_trabajo', 'observaciones', 'condicion_final',
        'costo_mano_obra', 'costo_otros', 'creado_por', 'autorizado_por', 'supervisor_id',
    ];

    protected function casts(): array
    {
        return [
            'programado_inicio' => 'datetime',
            'programado_fin' => 'datetime',
            'autorizado_at' => 'datetime',
            'iniciado_at' => 'datetime',
            'completado_at' => 'datetime',
            'supervisado_at' => 'datetime',
            'cerrado_at' => 'datetime',
            'costo_mano_obra' => 'decimal:2',
            'costo_otros' => 'decimal:2',
        ];
    }

    public function auditoriaModulo(): string
    {
        return 'mantenimientos';
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoMantenimiento::class, 'tipo_id');
    }

    public function prioridad(): BelongsTo
    {
        return $this->belongsTo(Prioridad::class);
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoMantenimiento::class, 'estado_id');
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudMantenimiento::class, 'solicitud_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanMantenimiento::class, 'plan_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'autorizado_por');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'supervisor_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionMantenimiento::class);
    }

    public function observaciones(): HasMany
    {
        return $this->hasMany(ObservacionMantenimiento::class);
    }

    public function materiales(): HasMany
    {
        return $this->hasMany(MaterialMantenimiento::class);
    }

    public function historialEstados(): HasMany
    {
        return $this->hasMany(HistorialEstadoMantenimiento::class)->orderBy('cambiado_at');
    }

    public function reprogramaciones(): HasMany
    {
        return $this->hasMany(ReprogramacionMantenimiento::class);
    }

    public function normas(): BelongsToMany
    {
        return $this->belongsToMany(Norma::class, 'mantenimiento_norma');
    }

    public function tecnicos(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'asignaciones_mantenimiento', 'mantenimiento_id', 'tecnico_id')
            ->withPivot(['es_principal', 'asignado_por', 'asignado_at', 'desasignado_at', 'notas']);
    }
}
