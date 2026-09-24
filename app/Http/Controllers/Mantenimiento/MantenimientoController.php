<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mantenimiento\GuardarMantenimientoRequest;
use App\Models\Equipo;
use App\Models\EstadoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\Material;
use App\Models\Prioridad;
use App\Models\Sucursal;
use App\Models\TipoMantenimiento;
use App\Models\Ubicacion;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\CicloMantenimiento;
use App\Support\Folios;
use App\Support\SeleccionSucursal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Órdenes de mantenimiento. Especificación v2.0 §5.12 / RF-040..051.
 */
class MantenimientoController extends Controller
{
    private const ORDENABLES = ['folio', 'programado_inicio', 'completado_at', 'id'];

    private const POR_PAGINA = 15;

    public function index(Request $request): Response
    {
        $this->authorize('mantenimientos.ver');

        $usuario = $request->user();
        $orden = in_array($request->query('orden'), self::ORDENABLES, true) ? $request->query('orden') : 'id';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';
        $texto = fn (string $c): ?string => filled($request->query($c)) ? trim((string) $request->query($c)) : null;
        $sucursalId = SeleccionSucursal::resolver($request->query('sucursal_id'));

        $mantenimientos = Mantenimiento::query()
            ->with([
                'equipo:id,codigo_activo,descripcion',
                'ubicacion:id,nombre',
                'sucursal:id,nombre',
                'tipo:id,nombre,categoria',
                'prioridad:id,nombre,color',
                'estado:id,nombre,clave',
                'tecnicos:id,nombre',
            ])
            // El técnico solo ve las órdenes que tiene asignadas (§4).
            ->when(
                $usuario->hasRole('tecnico') && ! $usuario->hasAnyRole(['superadministrador', 'supervisor']),
                fn (Builder $q) => $q->whereHas('asignaciones', fn (Builder $a) => $a->where('tecnico_id', $usuario->id)->whereNull('desasignado_at')),
            )
            ->when($texto('folio'), fn (Builder $q, $v) => $q->where('folio', 'like', "%{$v}%"))
            ->when($texto('equipo'), fn (Builder $q, $v) => $q->where(fn (Builder $w) => $w
                ->whereHas('equipo', fn (Builder $e) => $e->where('codigo_activo', 'like', "%{$v}%")->orWhere('descripcion', 'like', "%{$v}%"))
                ->orWhereHas('ubicacion', fn (Builder $u) => $u->where('nombre', 'like', "%{$v}%"))))
            ->when($sucursalId, fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->when($request->integer('ubicacion_id'), fn (Builder $q, $v) => $q->where('ubicacion_id', $v))
            ->when($request->integer('tipo_id'), fn (Builder $q, $v) => $q->where('tipo_id', $v))
            ->when($request->integer('prioridad_id'), fn (Builder $q, $v) => $q->where('prioridad_id', $v))
            ->when($request->integer('estado_id'), fn (Builder $q, $v) => $q->where('estado_id', $v))
            ->when($request->integer('tecnico_id'), fn (Builder $q, $v) => $q->whereHas('asignaciones', fn (Builder $a) => $a->where('tecnico_id', $v)))
            ->when($request->filled('desde'), fn (Builder $q) => $q->whereDate('programado_inicio', '>=', $request->query('desde')))
            ->when($request->filled('hasta'), fn (Builder $q) => $q->whereDate('programado_inicio', '<=', $request->query('hasta')))
            ->when($request->filled('registrado_por'), fn (Builder $q) => $q->whereIn(
                'id',
                Auditoria::idsCreadosPor(Mantenimiento::class, trim((string) $request->query('registrado_por'))),
            ))
            ->orderBy($orden, $dir)
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        $creadores = Auditoria::creadoPorMasivo(Mantenimiento::class, $mantenimientos->pluck('id'));
        $mantenimientos->through(fn (Mantenimiento $m) => [
            'id' => $m->id,
            'folio' => $m->folio,
            'objetivo' => $m->equipo
                ? ['tipo' => 'equipo', 'texto' => "{$m->equipo->codigo_activo} · {$m->equipo->descripcion}"]
                : ($m->ubicacion ? ['tipo' => 'ubicacion', 'texto' => $m->ubicacion->nombre] : null),
            'tipo' => $m->tipo,
            'sucursal' => $m->sucursal?->nombre,
            'prioridad' => $m->prioridad,
            'estado' => $m->estado,
            'tecnicos' => $m->tecnicos->pluck('nombre')->implode(', '),
            'programado_inicio' => $m->programado_inicio,
            'completado_at' => $m->completado_at,
            'creado_por' => $creadores[$m->id]['usuario'] ?? null,
            'creado_en' => $creadores[$m->id]['fecha'] ?? null,
        ]);

        return Inertia::render('Mantenimiento/Ordenes/Index', [
            'mantenimientos' => $mantenimientos,
            'sucursalId' => $sucursalId,
            'filtros' => $request->only(['folio', 'equipo', 'tipo_id', 'prioridad_id', 'estado_id', 'tecnico_id', 'desde', 'hasta', 'registrado_por']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
            'catalogos' => $this->catalogos($sucursalId),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('mantenimientos.crear');

        return Inertia::render('Mantenimiento/Ordenes/Form', [
            'equipos' => Equipo::orderBy('codigo_activo')->get(['id', 'codigo_activo', 'descripcion', 'sucursal_id']),
            'ubicaciones' => Ubicacion::activos()->orderBy('ruta')->get(['id', 'nombre', 'profundidad', 'sucursal_id']),
            'catalogos' => $this->catalogos(),
            'preseleccion' => [
                'equipo_id' => $request->integer('equipo_id') ?: null,
                'ubicacion_id' => $request->integer('ubicacion_id') ?: null,
            ],
        ]);
    }

    public function store(GuardarMantenimientoRequest $request): RedirectResponse
    {
        $sucursalId = $request->filled('ubicacion_id')
            ? Ubicacion::findOrFail($request->integer('ubicacion_id'))->sucursal_id
            : Equipo::findOrFail($request->integer('equipo_id'))->sucursal_id;
        $inicial = CicloMantenimiento::estado('autorizado');

        $mantenimiento = DB::transaction(function () use ($request, $sucursalId, $inicial) {
            $mantenimiento = Mantenimiento::create([
                ...$request->safe()->except('normas'),
                'folio' => Folios::mantenimiento(),
                'sucursal_id' => $sucursalId,
                'estado_id' => $inicial->id,
                'creado_por' => $request->user()->id,
                'autorizado_por' => $request->user()->id,
                'autorizado_at' => now(),
            ]);
            $mantenimiento->normas()->sync($request->input('normas', []));
            $mantenimiento->historialEstados()->create([
                'estado_destino_id' => $inicial->id,
                'cambiado_por' => $request->user()->id,
                'nota' => 'Orden creada manualmente',
                'cambiado_at' => now(),
            ]);

            return $mantenimiento;
        });

        return redirect()->route('mantenimientos.show', $mantenimiento)
            ->with('exito', "Orden {$mantenimiento->folio} creada.");
    }

    public function show(Mantenimiento $mantenimiento): Response
    {
        $this->authorize('mantenimientos.ver');

        $mantenimiento->load([
            'equipo:id,codigo_activo,descripcion,tipo_id',
            'ubicacion:id,nombre',
            'sucursal:id,nombre',
            'tipo:id,nombre,categoria',
            'prioridad:id,nombre,color',
            'estado:id,nombre,clave',
            'solicitud:id,folio',
            'plan:id,nombre',
            'creadoPor:id,nombre',
            'autorizadoPor:id,nombre',
            'supervisor:id,nombre',
            'asignaciones.tecnico:id,nombre',
            'asignaciones.asignadoPor:id,nombre',
            'observaciones.usuario:id,nombre',
            'materiales.material:id,nombre,unidad',
            'normas:id,codigo,nombre',
            'historialEstados.estadoOrigen:id,nombre',
            'historialEstados.estadoDestino:id,nombre',
            'historialEstados.cambiadoPor:id,nombre',
            'reprogramaciones.reprogramadoPor:id,nombre',
            'documentos',
        ]);

        return Inertia::render('Mantenimiento/Ordenes/Show', [
            'mantenimiento' => $mantenimiento,
            'sello' => $mantenimiento->selloAuditoria(),
            'transicionesPosibles' => CicloMantenimiento::siguientes($mantenimiento->estado->clave),
            'faltantesCierre' => CicloMantenimiento::validarCierre($mantenimiento),
            'checklistCierre' => CicloMantenimiento::checklistCierre($mantenimiento),
            'catalogos' => [
                'tecnicos' => $this->tecnicosConRecomendacion($mantenimiento),
                'materiales' => Material::activos()->orderBy('nombre')->get(['id', 'nombre', 'unidad', 'costo_referencia']),
            ],
        ]);
    }

    /**
     * Técnicos activos, marcando como "recomendado" a quien tenga una
     * especialidad de catálogo (tipo de equipo o de mantenimiento) que
     * coincide con esta orden — para delegar con mejor criterio (Fase 38).
     */
    private function tecnicosConRecomendacion(Mantenimiento $mantenimiento): Collection
    {
        return Usuario::role('tecnico')
            ->where('estado', 'activo')
            ->with(['especialidadesEquipo:id,nombre', 'especialidadesMantenimiento:id,nombre'])
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn (Usuario $t) => [
                'id' => $t->id,
                'nombre' => $t->nombre,
                'recomendado' => ($mantenimiento->equipo?->tipo_id && $t->especialidadesEquipo->pluck('id')->contains($mantenimiento->equipo->tipo_id))
                    || ($mantenimiento->tipo_id && $t->especialidadesMantenimiento->pluck('id')->contains($mantenimiento->tipo_id)),
                // Resumen de cualidades para mostrar al elegirlo en "Asignar técnico" (§16-17).
                'cualidades' => $t->especialidadesEquipo->pluck('nombre')
                    ->concat($t->especialidadesMantenimiento->pluck('nombre'))
                    ->values()
                    ->all(),
            ])
            ->sortByDesc('recomendado')
            ->values();
    }

    public function edit(Mantenimiento $mantenimiento): RedirectResponse
    {
        // La captura de trabajo se hace desde la ficha de la orden.
        return redirect()->route('mantenimientos.show', $mantenimiento);
    }

    /** Captura de diagnóstico, actividades, observaciones y costos. RF-047. */
    public function update(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->authorize('mantenimientos.editar');
        $this->verificarTecnicoAsignado($mantenimiento);

        $datos = $request->validate([
            'diagnostico' => ['nullable', 'string'],
            'descripcion_trabajo' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'condicion_final' => ['nullable', 'string', 'max:255'],
            'costo_mano_obra' => ['nullable', 'numeric', 'min:0'],
            'costo_otros' => ['nullable', 'numeric', 'min:0'],
        ]);

        $mantenimiento->update($datos);

        return back()->with('exito', 'Información de la orden actualizada.');
    }

    /** Transición de estado validada contra la máquina de estados. RF-045. */
    public function transicion(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->authorize('mantenimientos.editar');

        $datos = $request->validate([
            'estado' => ['required', 'string'],
            'nota' => ['nullable', 'string', 'max:500'],
        ]);

        $origen = $mantenimiento->estado->clave;
        $destino = $datos['estado'];

        if (! CicloMantenimiento::permite($origen, $destino)) {
            return back()->with('error', "Transición no permitida: {$origen} → {$destino}.");
        }

        if (in_array($destino, ['supervisado', 'cerrado'], true)) {
            $this->authorize('mantenimientos.supervisar');
        }
        if ($destino === 'cerrado') {
            $this->authorize('mantenimientos.cerrar');
            $faltantes = CicloMantenimiento::validarCierre($mantenimiento);
            if ($faltantes !== []) {
                return back()->with('error', 'No se puede cerrar: '.implode(' ', $faltantes));
            }
        }

        DB::transaction(function () use ($mantenimiento, $destino, $datos, $request): void {
            $estadoDestino = CicloMantenimiento::estado($destino);
            $estadoOrigenId = $mantenimiento->estado_id;

            CicloMantenimiento::aplicarSello($mantenimiento, $destino);
            $mantenimiento->estado_id = $estadoDestino->id;
            if ($destino === 'supervisado') {
                $mantenimiento->supervisor_id = $request->user()->id;
            }
            $mantenimiento->save();

            $mantenimiento->historialEstados()->create([
                'estado_origen_id' => $estadoOrigenId,
                'estado_destino_id' => $estadoDestino->id,
                'cambiado_por' => $request->user()->id,
                'nota' => $datos['nota'] ?? null,
                'cambiado_at' => now(),
            ]);
        });

        return back()->with('exito', "Orden movida a «{$destino}».");
    }

    /** Reprogramación con motivo obligatorio; queda en historial. RF-051. */
    public function reprogramar(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->authorize('mantenimientos.editar');

        $datos = $request->validate([
            'programado_inicio' => ['required', 'date', 'after_or_equal:today'],
            'programado_fin' => ['nullable', 'date', 'after:programado_inicio'],
            'motivo' => ['required', 'string', 'max:500'],
        ], [
            'programado_inicio.after_or_equal' => 'La nueva fecha no puede ser anterior a hoy.',
            'programado_fin.after' => 'La fecha de fin debe ser posterior a la de inicio.',
        ]);

        DB::transaction(function () use ($mantenimiento, $datos, $request): void {
            $mantenimiento->reprogramaciones()->create([
                'inicio_anterior' => $mantenimiento->programado_inicio,
                'fin_anterior' => $mantenimiento->programado_fin,
                'inicio_nuevo' => $datos['programado_inicio'],
                'fin_nuevo' => $datos['programado_fin'] ?? null,
                'motivo' => $datos['motivo'],
                'reprogramado_por' => $request->user()->id,
                'created_at' => now(),
            ]);

            $mantenimiento->update([
                'programado_inicio' => $datos['programado_inicio'],
                'programado_fin' => $datos['programado_fin'] ?? null,
            ]);
        });

        return back()->with('exito', 'Orden reprogramada.');
    }

    public function destroy(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        abort_unless($request->user()->hasRole('superadministrador'), 403);

        $mantenimiento->delete();

        return redirect()->route('mantenimientos.index')->with('exito', 'Orden eliminada.');
    }

    private function verificarTecnicoAsignado(Mantenimiento $mantenimiento): void
    {
        $usuario = request()->user();

        if ($usuario->hasAnyRole(['superadministrador', 'supervisor'])) {
            return;
        }

        // El técnico solo modifica trabajos que tiene asignados (§7).
        abort_unless(
            $mantenimiento->asignaciones()->where('tecnico_id', $usuario->id)->whereNull('desasignado_at')->exists(),
            403,
            'No tienes esta orden asignada.',
        );
    }

    /** @return array<string, mixed> */
    private function catalogos(?int $sucursalId = null): array
    {
        $puedeCrear = request()->user()?->can('mantenimientos.crear');

        return [
            'sucursales' => Sucursal::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'tipos' => TipoMantenimiento::activos()->orderBy('nombre')->get(['id', 'nombre', 'categoria']),
            'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre', 'color']),
            'estados' => EstadoMantenimiento::activos()->orderBy('orden')->get(['id', 'nombre', 'clave']),
            'tecnicos' => Usuario::role('tecnico')->where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']),
            'equipos' => $puedeCrear
                ? Equipo::when($sucursalId, fn ($q, $v) => $q->where('sucursal_id', $v))->orderBy('codigo_activo')->get(['id', 'codigo_activo', 'descripcion'])
                : [],
            'ubicaciones' => $puedeCrear
                ? Ubicacion::activos()->when($sucursalId, fn ($q, $v) => $q->where('sucursal_id', $v))->orderBy('ruta')->get(['id', 'nombre', 'profundidad', 'sucursal_id'])
                : [],
        ];
    }
}
