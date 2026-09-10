<?php

namespace App\Models;

use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Plan de mantenimiento preventivo de un equipo. Especificación v2.0 §5.8 / RF-041.
 */
class PlanMantenimiento extends Model
{
    use EsAuditable, HasFactory, SoftDeletes, TieneEstadoActivo;

    protected $table = 'planes_mantenimiento';

    protected $fillable = [
        'equipo_id', 'tipo_mantenimiento_id', 'nombre',
        'tipo_frecuencia', 'valor_frecuencia', 'regla_personalizada',
        'fecha_inicio', 'proxima_fecha', 'dias_aviso_anticipado',
        'norma_id', 'formato_id', 'prioridad_id', 'tecnico_id', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'regla_personalizada' => 'array',
            'fecha_inicio' => 'date',
            'proxima_fecha' => 'date',
            'valor_frecuencia' => 'integer',
            'dias_aviso_anticipado' => 'integer',
        ];
    }

    public function auditoriaModulo(): string
    {
        return 'planes';
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoMantenimiento::class, 'tipo_mantenimiento_id');
    }

    public function norma(): BelongsTo
    {
        return $this->belongsTo(Norma::class);
    }

    public function formato(): BelongsTo
    {
        return $this->belongsTo(Formato::class);
    }

    public function prioridad(): BelongsTo
    {
        return $this->belongsTo(Prioridad::class);
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'tecnico_id');
    }

    public function ocurrencias(): HasMany
    {
        return $this->hasMany(OcurrenciaPlanMantenimiento::class, 'plan_id');
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'plan_id');
    }

    public function estaVencido(): bool
    {
        return $this->proxima_fecha !== null && $this->proxima_fecha->isPast();
    }
}
