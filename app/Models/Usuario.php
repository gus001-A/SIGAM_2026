<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneEstadoActivo;
use App\Notifications\RestablecerContrasena;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuario del sistema. Especificación v2.0 §5.3-5.4 / §8.
 * Columnas de auth (email, password, remember_token) se mantienen en inglés
 * por ser plomería del framework.
 */
class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use ConvierteMayusculas, EsAuditable, HasFactory, HasRoles, Notifiable, SoftDeletes, TieneEstadoActivo;

    protected $table = 'usuarios';

    /** @var string El guard por defecto para spatie/permission. */
    protected string $guard_name = 'web';

    protected $fillable = [
        'nombre',
        'name', // alias de `nombre` (compatibilidad con Breeze)
        'apellidos',
        'email',
        'telefono',
        'password',
        'sucursal_id',
        'estado',
        'ultimo_acceso_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Se expone `name` (alias de `nombre`) para compatibilidad con el
     * scaffolding de Breeze y con `$page.props.auth.user` en Vue.
     *
     * @var list<string>
     */
    protected $appends = ['name'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acceso_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Envía el correo de recuperación de contraseña en español. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RestablecerContrasena($token));
    }

    // --- Relaciones -----------------------------------------------------

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function sucursalesResponsable(): HasMany
    {
        return $this->hasMany(Sucursal::class, 'responsable_id');
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(SolicitudMantenimiento::class, 'solicitado_por');
    }

    public function mantenimientosCreados(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'creado_por');
    }

    public function mantenimientosSupervisados(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'supervisor_id');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionMantenimiento::class, 'tecnico_id');
    }

    /** Órdenes en las que el usuario participa como técnico asignado. */
    public function mantenimientosAsignados(): BelongsToMany
    {
        return $this->belongsToMany(Mantenimiento::class, 'asignaciones_mantenimiento', 'tecnico_id', 'mantenimiento_id')
            ->withPivot(['es_principal', 'asignado_por', 'asignado_at', 'desasignado_at', 'notas']);
    }

    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }

    /** Tareas en las que el usuario participa como responsable. */
    public function tareasAsignadas(): BelongsToMany
    {
        return $this->belongsToMany(Tarea::class, 'tarea_responsables', 'usuario_id', 'tarea_id')
            ->withPivot(['es_principal', 'asignado_por', 'asignado_at', 'desasignado_at', 'notas']);
    }

    /** Tipos de equipo en los que el técnico es especialista (para delegar mejor). */
    public function especialidadesEquipo(): BelongsToMany
    {
        return $this->belongsToMany(TipoEquipo::class, 'usuario_tipo_equipo');
    }

    /** Tipos de mantenimiento en los que el técnico es especialista. */
    public function especialidadesMantenimiento(): BelongsToMany
    {
        return $this->belongsToMany(TipoMantenimiento::class, 'usuario_tipo_mantenimiento');
    }

    public function registrosAuditoria(): HasMany
    {
        return $this->hasMany(RegistroAuditoria::class);
    }

    // --- Accesores -----------------------------------------------------

    /** Alias `name` <-> columna `nombre` (compatibilidad con Breeze). */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->nombre,
            set: fn ($value) => ['nombre' => $value],
        );
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombre.' '.(string) $this->apellidos);
    }

    protected function camposMayusculas(): array
    {
        return ['nombre', 'apellidos'];
    }
}
