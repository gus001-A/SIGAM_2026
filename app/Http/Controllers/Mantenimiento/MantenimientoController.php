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
use App\Models\Usuario;
use App\Support\CicloMantenimiento;
use App\Support\Folios;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $mantenimientos = Mantenimiento::query()
            ->with([
                'equipo:id,codigo_activo,descripcion',
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
            ->when($texto('equipo'), fn (Builder $q, $v) => $q->whereHas('equipo', fn (Builder $e) => $e
                ->where('codigo_activo', 'like', "%{$v}%")->orWhere('descripcion', 'like', "%{$v}%")))
            ->when($request->integer('sucursal_id'), fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->when($request->integer('tipo_id'), fn (Builder $q, $v) => $q->where('tipo_id', $v))
            ->when($request->integer('prioridad_id'), fn (Builder $q, $v) => $q->where('prioridad_id', $v))
            ->when($request->integer('estado_id'), fn (Builder $q, $v) => $q->where('estado_id', $v))
            ->when($request->integer('tecnico_id'), fn (Builder $q, $v) => $q->whereHas('asignaciones', fn (Builder $a) => $a->where('tecnico_id', $v)))
            ->orderBy($orden, $dir)
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Mantenimiento $m) => [
                'id' => $m->id,
                'folio' => $m->folio,
                'equipo' => $m->equipo ? "{$m->equipo->codigo_activo} · {$m->equipo->descripcion}" : null,
                'tipo' => $m->tipo,
                'sucursal' => $m->sucursal?->nombre,
                'prioridad' => $m->prioridad,
                'estado' => $m->estado,
                'tecnicos' => $m->tecnicos->pluck('nombre')->implode(', '),
                'programado_inicio' => $m->programado_inicio,
                'completado_at' => $m->completado_at,
            ]);

        return Inertia::render('Mantenimiento/Ordenes/Index', [
            'mantenimientos' => $mantenimientos,
            'filtros' => $request->only(['folio', 'equipo', 'sucursal_id', 'tipo_id', 'prioridad_id', 'estado_id', 'tecnico_id']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
            'catalogos' => $this->catalogos(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('mantenimientos.crear');

        return Inertia::render('Mantenimiento/Ordenes/Form', [
            'equipos' => Equipo::orderBy('codigo_activo')->get(['id', 'codigo_activo', 'descripcion', 'sucursal_id']),
            'catalogos' => $this->catalogos(),
        ]);
    }

    public function store(GuardarMantenimientoRequest $request): RedirectResponse
    {
        $equipo = Equipo::findOrFail($request->integer('equipo_id'));
        $inicial = CicloMantenimiento::estado('autorizado');

        $mantenimiento = DB::transaction(function () use ($request, $equipo, $inicial) {
            $mantenimiento = Mantenimiento::create([
                ...$request->safe()->except('normas'),
                'folio' => Folios::mantenimiento(),
                'sucursal_id' => $equipo->sucursal_id,
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
            'equipo:id,codigo_activo,descripcion',
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
            'transicionesPosibles' => CicloMantenimiento::siguientes($mantenimiento->estado->clave),
            'faltantesCierre' => CicloMantenimiento::validarCierre($mantenimiento),
            'catalogos' => [
                'tecnicos' => Usuario::role('tecnico')->where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']),
                'materiales' => Material::activos()->orderBy('nombre')->get(['id', 'nombre', 'unidad', 'costo_referencia']),
            ],
        ]);
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
            'programado_inicio' => ['required', 'date'],
            'programado_fin' => ['nullable', 'date', 'after_or_equal:programado_inicio'],
            'motivo' => ['required', 'string', 'max:500'],
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
    private function catalogos(): array
    {
        return [
            'sucursales' => Sucursal::orderBy('nombre')->get(['id', 'nombre']),
            'tipos' => TipoMantenimiento::activos()->orderBy('nombre')->get(['id', 'nombre', 'categoria']),
            'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre', 'color']),
            'estados' => EstadoMantenimiento::activos()->orderBy('orden')->get(['id', 'nombre', 'clave']),
            'tecnicos' => Usuario::role('tecnico')->where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']),
            'equipos' => request()->user()?->can('mantenimientos.crear')
                ? Equipo::orderBy('codigo_activo')->get(['id', 'codigo_activo', 'descripcion'])
                : [],
        ];
    }
}
