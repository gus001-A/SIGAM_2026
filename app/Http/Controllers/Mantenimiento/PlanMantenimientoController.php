<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use App\Models\Equipo;
use App\Models\Formato;
use App\Models\Mantenimiento;
use App\Models\Norma;
use App\Models\PlanMantenimiento;
use App\Models\Prioridad;
use App\Models\Sucursal;
use App\Models\TipoMantenimiento;
use App\Models\Ubicacion;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\CicloMantenimiento;
use App\Support\Folios;
use App\Support\Frecuencia;
use App\Support\SeleccionSucursal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Planes de mantenimiento preventivo. Especificación v2.0 §5.8 / RF-041..042.
 */
class PlanMantenimientoController extends Controller
{
    private const FRECUENCIAS = ['dias', 'semanal', 'mensual', 'bimestral', 'trimestral', 'semestral', 'anual', 'personalizada'];

    /** Columnas por las que se permite ordenar el listado. */
    private const ORDENABLES = ['proxima_fecha', 'nombre', 'ocurrencias_count', 'created_at'];

    /** Registros por página del listado. */
    private const POR_PAGINA = 15;

    public function index(Request $request): Response
    {
        $this->authorize('mantenimientos.ver');

        $orden = in_array($request->query('orden'), self::ORDENABLES, true) ? $request->query('orden') : 'proxima_fecha';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $texto = fn (string $clave): ?string => filled($request->query($clave)) ? trim((string) $request->query($clave)) : null;
        $sucursalId = SeleccionSucursal::resolver($request->query('sucursal_id'));

        $planes = PlanMantenimiento::query()
            ->with(['equipo:id,codigo_activo,descripcion', 'ubicacion:id,nombre', 'sucursal:id,nombre', 'tipo:id,nombre', 'tecnico:id,nombre'])
            ->withCount('ocurrencias')
            // Filtros por columna
            ->when($texto('equipo'), fn (Builder $q, $v) => $q->where(fn (Builder $w) => $w
                ->whereHas('equipo', fn (Builder $e) => $e->where('codigo_activo', 'like', "%{$v}%")->orWhere('descripcion', 'like', "%{$v}%"))
                ->orWhereHas('ubicacion', fn (Builder $u) => $u->where('nombre', 'like', "%{$v}%"))))
            ->when($texto('nombre'), fn (Builder $q, $v) => $q->where('nombre', 'like', "%{$v}%"))
            ->when($sucursalId, fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->when($request->integer('tipo_mantenimiento_id'), fn (Builder $q, $v) => $q->where('tipo_mantenimiento_id', $v))
            ->when($request->integer('tecnico_id'), fn (Builder $q, $v) => $q->where('tecnico_id', $v))
            ->when($request->query('frecuencia'), fn (Builder $q, $v) => $q->where('tipo_frecuencia', $v))
            ->when($request->boolean('vencidos'), fn (Builder $q) => $q->whereDate('proxima_fecha', '<', today()))
            ->when($request->filled('desde'), fn (Builder $q) => $q->whereDate('proxima_fecha', '>=', $request->query('desde')))
            ->when($request->filled('hasta'), fn (Builder $q) => $q->whereDate('proxima_fecha', '<=', $request->query('hasta')))
            ->when($request->filled('registrado_por'), fn (Builder $q) => $q->whereIn(
                'id',
                Auditoria::idsCreadosPor(PlanMantenimiento::class, trim((string) $request->query('registrado_por'))),
            ))
            ->orderBy($orden, $dir)
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        $creadores = Auditoria::creadoPorMasivo(PlanMantenimiento::class, $planes->pluck('id'));
        $planes->through(fn (PlanMantenimiento $p) => [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'objetivo' => $p->equipo
                ? ['tipo' => 'equipo', 'texto' => "{$p->equipo->codigo_activo} · {$p->equipo->descripcion}"]
                : ($p->ubicacion ? ['tipo' => 'ubicacion', 'texto' => $p->ubicacion->nombre] : null),
            'sucursal' => $p->sucursal?->nombre,
            'tipo' => $p->tipo?->nombre,
            'frecuencia' => $p->tipo_frecuencia,
            'valor_frecuencia' => $p->valor_frecuencia,
            'proxima_fecha' => $p->proxima_fecha?->toDateString(),
            'vencido' => $p->proxima_fecha !== null && $p->proxima_fecha->isPast(),
            'tecnico' => $p->tecnico?->nombre,
            'ocurrencias_count' => $p->ocurrencias_count,
            'estado' => $p->estado,
            'creado_por' => $creadores[$p->id]['usuario'] ?? null,
            'creado_en' => $creadores[$p->id]['fecha'] ?? null,
        ]);

        return Inertia::render('Mantenimiento/Planes/Index', [
            'planes' => $planes,
            'sucursalId' => $sucursalId,
            'filtros' => $request->only(['equipo', 'nombre', 'tipo_mantenimiento_id', 'tecnico_id', 'frecuencia', 'vencidos', 'desde', 'hasta', 'registrado_por']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
            'catalogos' => [
                'sucursales' => Sucursal::activos()->orderBy('nombre')->get(['id', 'nombre']),
                'tipos' => TipoMantenimiento::activos()->where('categoria', 'preventivo')->orderBy('nombre')->get(['id', 'nombre']),
                'tecnicos' => Usuario::role('tecnico')->where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']),
                'frecuencias' => self::FRECUENCIAS,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('mantenimientos.crear');

        return Inertia::render('Mantenimiento/Planes/Form', [
            'plan' => null,
            'preseleccion' => ['equipo_id' => $request->integer('equipo_id') ?: null],
            'catalogos' => $this->catalogos(),
            'frecuencias' => self::FRECUENCIAS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('mantenimientos.crear');

        $datos = $this->validar($request, esNuevo: true);
        $datos['proxima_fecha'] = $this->calcularProxima($datos);

        $plan = PlanMantenimiento::create($datos);

        return redirect()->route('planes.show', $plan)->with('exito', 'Plan preventivo creado.');
    }

    public function show(PlanMantenimiento $plan): Response
    {
        $this->authorize('mantenimientos.ver');

        $plan->load(['equipo:id,codigo_activo,descripcion', 'ubicacion:id,nombre', 'sucursal:id,nombre', 'tipo:id,nombre', 'norma:id,codigo', 'formato:id,nombre', 'tecnico:id,nombre']);

        return Inertia::render('Mantenimiento/Planes/Show', [
            'plan' => $plan,
            'sello' => $plan->selloAuditoria(),
            'ocurrencias' => $plan->ocurrencias()
                ->with('mantenimiento:id,folio,estado_id')
                ->orderBy('fecha_programada')
                ->get(),
        ]);
    }

    public function edit(PlanMantenimiento $plan): Response
    {
        $this->authorize('mantenimientos.editar');

        return Inertia::render('Mantenimiento/Planes/Form', [
            'plan' => $plan,
            'catalogos' => $this->catalogos(),
            'frecuencias' => self::FRECUENCIAS,
        ]);
    }

    public function update(Request $request, PlanMantenimiento $plan): RedirectResponse
    {
        $this->authorize('mantenimientos.editar');

        $datos = $this->validar($request);
        $datos['proxima_fecha'] = $this->calcularProxima($datos);

        $plan->update($datos);

        return redirect()->route('planes.show', $plan)->with('exito', 'Plan actualizado.');
    }

    public function destroy(PlanMantenimiento $plan): RedirectResponse
    {
        $this->authorize('mantenimientos.editar');

        $plan->update(['estado' => 'inactivo']);
        $plan->delete();

        return redirect()->route('planes.index')->with('exito', 'Plan desactivado.');
    }

    /** Genera las próximas ocurrencias calendarizadas del plan. RF-042. */
    public function generarOcurrencias(Request $request, PlanMantenimiento $plan): RedirectResponse
    {
        $this->authorize('mantenimientos.editar');

        $cantidad = (int) $request->validate(['cantidad' => ['required', 'integer', 'min:1', 'max:36']])['cantidad'];

        $base = $plan->proxima_fecha
            ? Carbon::parse($plan->proxima_fecha)->subDay()
            : Carbon::parse($plan->fecha_inicio ?? now());

        $fechas = Frecuencia::proximas($base, $plan->tipo_frecuencia, (int) $plan->valor_frecuencia, $cantidad);

        foreach ($fechas as $fecha) {
            $plan->ocurrencias()->firstOrCreate(
                ['fecha_programada' => $fecha->toDateString()],
                ['estado' => 'pendiente'],
            );
        }

        return back()->with('exito', count($fechas).' ocurrencias generadas.');
    }

    /** Elimina una ocurrencia programada que todavía no generó una orden (p. ej. si ya no aplica). */
    public function destroyOcurrencia(PlanMantenimiento $plan, int $ocurrencia): RedirectResponse
    {
        $this->authorize('mantenimientos.editar');

        $registro = $plan->ocurrencias()->findOrFail($ocurrencia);

        abort_if($registro->mantenimiento_id !== null, 422, 'Esta ocurrencia ya generó una orden y no se puede eliminar.');

        $registro->delete();

        return back()->with('exito', 'Ocurrencia eliminada.');
    }

    /** Crea una orden de mantenimiento a partir de una ocurrencia pendiente. */
    public function generarOrden(Request $request, PlanMantenimiento $plan): RedirectResponse
    {
        $this->authorize('mantenimientos.crear');

        $ocurrenciaId = (int) $request->validate([
            'ocurrencia_id' => ['required', 'integer', Rule::exists('ocurrencias_plan_mantenimiento', 'id')->where('plan_id', $plan->id)],
        ])['ocurrencia_id'];

        $mantenimiento = DB::transaction(function () use ($plan, $ocurrenciaId, $request) {
            $ocurrencia = $plan->ocurrencias()->lockForUpdate()->findOrFail($ocurrenciaId);

            if ($ocurrencia->mantenimiento_id) {
                return $ocurrencia->mantenimiento;
            }

            $inicial = CicloMantenimiento::estado('autorizado');
            $mantenimiento = Mantenimiento::create([
                'folio' => Folios::mantenimiento(),
                'plan_id' => $plan->id,
                'equipo_id' => $plan->equipo_id,
                'ubicacion_id' => $plan->ubicacion_id,
                'sucursal_id' => $plan->sucursal_id ?? $plan->equipo?->sucursal_id ?? $plan->ubicacion?->sucursal_id,
                'tipo_id' => $plan->tipo_mantenimiento_id,
                'prioridad_id' => $plan->prioridad_id,
                'estado_id' => $inicial->id,
                'programado_inicio' => $ocurrencia->fecha_programada,
                'problema_reportado' => 'Mantenimiento preventivo programado: '.($plan->nombre ?? ''),
                'creado_por' => $request->user()->id,
                'autorizado_por' => $request->user()->id,
                'autorizado_at' => now(),
            ]);

            if ($plan->tecnico_id) {
                $mantenimiento->asignaciones()->create([
                    'tecnico_id' => $plan->tecnico_id,
                    'asignado_por' => $request->user()->id,
                    'es_principal' => true,
                    'asignado_at' => now(),
                ]);
            }

            $mantenimiento->historialEstados()->create([
                'estado_destino_id' => $inicial->id,
                'cambiado_por' => $request->user()->id,
                'nota' => 'Generada desde plan preventivo',
                'cambiado_at' => now(),
            ]);

            $ocurrencia->update([
                'estado' => 'generada',
                'mantenimiento_id' => $mantenimiento->id,
                'generada_at' => now(),
            ]);

            // Avanza la próxima fecha del plan.
            $plan->update([
                'proxima_fecha' => Frecuencia::siguiente(
                    Carbon::parse($ocurrencia->fecha_programada),
                    $plan->tipo_frecuencia,
                    (int) $plan->valor_frecuencia,
                )->toDateString(),
            ]);

            return $mantenimiento;
        });

        return redirect()->route('mantenimientos.show', $mantenimiento)
            ->with('exito', "Orden {$mantenimiento->folio} generada.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, bool $esNuevo = false): array
    {
        $datos = $request->validate([
            'equipo_id' => ['nullable', 'integer', Rule::exists('equipos', 'id')],
            'ubicacion_id' => ['nullable', 'integer', Rule::exists('ubicaciones', 'id')],
            'tipo_mantenimiento_id' => ['required', 'integer', Rule::exists('tipos_mantenimiento', 'id')],
            'nombre' => ['nullable', 'string', 'max:255'],
            'tipo_frecuencia' => ['required', Rule::in(self::FRECUENCIAS)],
            'valor_frecuencia' => ['required', 'integer', 'min:1'],
            'regla_personalizada' => ['nullable', 'array'],
            // Al crear, la fecha base para calendarizar no puede ser pasada; al
            // editar un plan ya existente se conserva la que tenga (puede ser
            // histórica sin que eso sea un error).
            'fecha_inicio' => $esNuevo ? ['nullable', 'date', 'after_or_equal:today'] : ['nullable', 'date'],
            'dias_aviso_anticipado' => ['required', 'integer', 'min:0', 'max:365'],
            'norma_id' => ['nullable', 'integer', Rule::exists('normas', 'id')],
            'formato_id' => ['nullable', 'integer', Rule::exists('formatos', 'id')],
            'prioridad_id' => ['nullable', 'integer', Rule::exists('prioridades', 'id')],
            'tecnico_id' => ['nullable', 'integer', Rule::exists('usuarios', 'id')],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ], [
            'fecha_inicio.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy.',
        ]);

        $tieneEquipo = filled($datos['equipo_id'] ?? null);
        $tieneUbicacion = filled($datos['ubicacion_id'] ?? null);

        if ($tieneEquipo === $tieneUbicacion) {
            throw ValidationException::withMessages([
                'equipo_id' => 'Selecciona un equipo o una instalación, no ambos ni ninguno.',
            ]);
        }

        $datos['sucursal_id'] = $tieneEquipo
            ? Equipo::findOrFail($datos['equipo_id'])->sucursal_id
            : Ubicacion::findOrFail($datos['ubicacion_id'])->sucursal_id;

        return $datos;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function calcularProxima(array $datos): string
    {
        $base = Carbon::parse($datos['fecha_inicio'] ?? now());

        return Frecuencia::siguiente($base, $datos['tipo_frecuencia'], (int) $datos['valor_frecuencia'])->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogos(): array
    {
        return [
            'equipos' => Equipo::orderBy('codigo_activo')->get(['id', 'codigo_activo', 'descripcion', 'sucursal_id']),
            'ubicaciones' => Ubicacion::activos()->orderBy('ruta')->get(['id', 'nombre', 'profundidad', 'sucursal_id']),
            'tipos' => TipoMantenimiento::activos()->where('categoria', 'preventivo')->orderBy('nombre')->get(['id', 'nombre']),
            'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre']),
            'normas' => Norma::activos()->orderBy('codigo')->get(['id', 'codigo', 'nombre']),
            'formatos' => Formato::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'tecnicos' => Usuario::role('tecnico')->where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']),
        ];
    }
}
