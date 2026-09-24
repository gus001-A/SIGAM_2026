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
use Illuminate\Support\Str;

/**
 * Activo / equipo con expediente digital. Especificación v2.0 §5.7-5.9.
 */
class Equipo extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, SoftDeletes, TieneDocumentos;

    protected $table = 'equipos';

    protected $fillable = [
        'codigo_activo', 'token_qr', 'codigo_barras', 'descripcion',
        'tipo_id', 'marca_id', 'modelo', 'numero_serie',
        'sucursal_id', 'ubicacion_id', 'proveedor_id', 'responsable_id', 'estado_id',
        'fecha_adquisicion', 'numero_factura', 'valor_adquisicion', 'garantia_hasta',
        'especificaciones', 'vida_util', 'notas',
        'motivo_baja', 'baja_por', 'baja_en',
    ];

    protected function casts(): array
    {
        return [
            'especificaciones' => 'array',
            'fecha_adquisicion' => 'date',
            'garantia_hasta' => 'date',
            'valor_adquisicion' => 'decimal:2',
            'baja_en' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Equipo $equipo): void {
            if (blank($equipo->token_qr)) {
                $equipo->token_qr = Str::lower(Str::random(40));
            }
        });
    }

    public function auditoriaModulo(): string
    {
        return 'equipos';
    }

    protected function camposMayusculas(): array
    {
        return ['codigo_activo', 'codigo_barras', 'descripcion', 'modelo', 'numero_serie', 'numero_factura', 'vida_util', 'notas', 'motivo_baja'];
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoEquipo::class, 'tipo_id');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function ubicacion(): BelongsTo
    {
        return $this->belongsTo(Ubicacion::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }

    public function bajaPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'baja_por');
    }

    public function estado(): BelongsTo
    {
        return $this->belongsTo(EstadoEquipo::class, 'estado_id');
    }

    public function normas(): BelongsToMany
    {
        return $this->belongsToMany(Norma::class, 'equipo_norma');
    }

    public function historialUbicacion(): HasMany
    {
        return $this->hasMany(HistorialUbicacionEquipo::class)->latest('cambiado_at');
    }

    public function planes(): HasMany
    {
        return $this->hasMany(PlanMantenimiento::class);
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class);
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(SolicitudMantenimiento::class);
    }
}
