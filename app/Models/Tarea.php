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
use Illuminate\Support\Collection;

/**
 * Tarea de seguimiento general (administrativa, no ligada a un equipo o
 * ubicación). Propuesta técnica SIGAM — anexo "TAREAS".
 */
class Tarea extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, SoftDeletes, TieneDocumentos;

    protected $table = 'tareas';

    protected $fillable = [
        'titulo', 'descripcion', 'fecha_limite', 'prioridad_id', 'clasificacion', 'proyecto_id', 'categoria_tarea_id',
        'estado', 'nota_avance', 'nota_cierre', 'nota_cancelacion',
        'costo', 'iniciada_at', 'realizada_at', 'cancelada_at', 'creado_por',
        'autorizada_at', 'autorizado_por', 'supervisada_at', 'supervisor_id', 'cerrada_at', 'reprogramada_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_limite' => 'date',
            'costo' => 'decimal:2',
            'iniciada_at' => 'datetime',
            'realizada_at' => 'datetime',
            'cancelada_at' => 'datetime',
            'autorizada_at' => 'datetime',
            'supervisada_at' => 'datetime',
            'cerrada_at' => 'datetime',
            'reprogramada_at' => 'datetime',
        ];
    }

    public function auditoriaModulo(): string
    {
        return 'tareas';
    }

    protected function camposMayusculas(): array
    {
        return ['titulo', 'descripcion', 'nota_avance', 'nota_cierre', 'nota_cancelacion'];
    }

    public function prioridad(): BelongsTo
    {
        return $this->belongsTo(Prioridad::class);
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    public function categoriaTarea(): BelongsTo
    {
        return $this->belongsTo(CategoriaTarea::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    /** Quién autorizó la tarea ya completada (paso "Autorizada"). */
    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'autorizado_por');
    }

    /** Quién la supervisó (paso "Supervisada"). */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'supervisor_id');
    }

    /** Responsables activos e históricos (pivote con historial de asignación). */
    public function responsables(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'tarea_responsables', 'tarea_id', 'usuario_id')
            ->withPivot(['es_principal', 'asignado_por', 'asignado_at', 'desasignado_at', 'notas']);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(TareaResponsable::class);
    }

    public function historialEstados(): HasMany
    {
        return $this->hasMany(HistorialEstadoTarea::class)->orderBy('cambiado_at');
    }

    /** Comentarios de supervisión (solo supervisor/auditor escriben; ver TareaController). */
    public function comentarios(): HasMany
    {
        return $this->hasMany(ComentarioTarea::class)->orderBy('created_at');
    }

    public function materiales(): HasMany
    {
        return $this->hasMany(MaterialTarea::class);
    }

    /**
     * Responsables activos que todavía no han hecho nada en la tarea desde que
     * se inició. Mientras haya alguno, la tarea no puede pasar a realizada
     * (salvo que el responsable principal decida cerrarla sin esperar).
     *
     * @return Collection<int, Usuario>
     */
    public function participantesPendientes(): Collection
    {
        return $this->asignaciones()->whereNull('desasignado_at')->with('usuario')->get()
            ->filter(fn (TareaResponsable $a) => ! $this->historialEstados()
                ->where('cambiado_por', $a->usuario_id)
                ->when($this->iniciada_at, fn ($q) => $q->where('cambiado_at', '>=', $this->iniciada_at))
                ->exists())
            ->map(fn (TareaResponsable $a) => $a->usuario)
            ->values();
    }

    /**
     * Días de retraso con los que se completó la tarea: diferencia entre la
     * fecha límite y el día en que se marcó como realizada. 0 si se entregó a
     * tiempo y null si aún no está realizada (no hay fecha de cierre todavía).
     */
    public function diasRetraso(): ?int
    {
        // `realizada_at` se pone al llegar a "completada" y ya no se toca
        // después, aunque la tarea siga avanzando a autorizada/supervisada/
        // cerrada — por eso no se exige que el estado sea exactamente ese.
        if (blank($this->realizada_at) || blank($this->fecha_limite)) {
            return null;
        }

        $diferencia = $this->fecha_limite->copy()->startOfDay()
            ->diff($this->realizada_at->copy()->startOfDay());

        return $diferencia->invert ? 0 : $diferencia->days;
    }

    /** El costo ya no se captura a mano: se recalcula a partir de los materiales. */
    public function recalcularCosto(): void
    {
        $total = $this->materiales()->whereNotNull('costo_unitario')->get()
            ->reduce(fn (?string $acc, MaterialTarea $m) => bcadd($acc ?? '0', $m->costo_total, 2), null);

        $this->forceFill(['costo' => $total])->saveQuietly();
    }
}
