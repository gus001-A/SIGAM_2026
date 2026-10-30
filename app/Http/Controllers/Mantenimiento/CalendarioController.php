<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use App\Models\CategoriaTarea;
use App\Models\Equipo;
use App\Models\EstadoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\OcurrenciaPlanMantenimiento;
use App\Models\Prioridad;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\TipoMantenimiento;
use App\Models\Ubicacion;
use App\Models\Usuario;
use App\Support\CicloTarea;
use App\Support\SeleccionSucursal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Calendario de trabajos. Especificación v2.0 §5.11.
 * Vista diaria/semanal/mensual con eventos preventivos y correctivos.
 */
class CalendarioController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('mantenimientos.ver');

        $desde = Carbon::parse($request->query('desde', now()->startOfMonth()->toDateString()))->startOfDay();
        $hasta = Carbon::parse($request->query('hasta', now()->endOfMonth()->toDateString()))->endOfDay();
        $sucursalId = SeleccionSucursal::resolver($request->query('sucursal_id'), $request->user());

        $ordenes = Mantenimiento::query()
            ->with(['equipo:id,codigo_activo,descripcion', 'ubicacion:id,nombre', 'sucursal:id,nombre', 'tipo:id,nombre,categoria', 'prioridad:id,nombre,color', 'estado:id,nombre,clave',
                'solicitud:id,folio', 'plan:id,nombre',
                'tecnicos' => fn ($q) => $q->select('usuarios.id', 'usuarios.nombre')->wherePivotNull('desasignado_at')])
            ->whereBetween('programado_inicio', [$desde, $hasta])
            ->when($sucursalId, fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->when($request->integer('tipo_id'), fn (Builder $q, $v) => $q->where('tipo_id', $v))
            ->when($request->integer('prioridad_id'), fn (Builder $q, $v) => $q->where('prioridad_id', $v))
            ->when($request->integer('estado_id'), fn (Builder $q, $v) => $q->where('estado_id', $v))
            ->when($request->integer('tecnico_id'), fn (Builder $q, $v) => $q->whereHas('asignaciones', fn (Builder $a) => $a->where('tecnico_id', $v)))
            ->get()
            ->map(fn (Mantenimiento $m) => [
                'tipo_evento' => 'orden',
                'id' => $m->id,
                'folio' => $m->folio,
                'titulo' => "{$m->folio} · ".($m->equipo?->codigo_activo ?? $m->ubicacion?->nombre ?? ''),
                'objetivo' => $m->equipo ? "{$m->equipo->codigo_activo} — {$m->equipo->descripcion}" : $m->ubicacion?->nombre,
                'objetivo_tipo' => $m->equipo ? 'equipo' : ($m->ubicacion ? 'ubicacion' : null),
                'nombre' => $m->equipo?->descripcion ?? $m->ubicacion?->nombre ?? $m->folio,
                'lugar' => $m->ubicacion?->nombre ?? $m->sucursal?->nombre,
                'inicio' => $m->programado_inicio,
                'fin' => $m->programado_fin,
                'categoria' => $m->tipo?->categoria,
                'color' => $m->prioridad?->color,
                'estado' => $m->estado?->clave,
                'estado_nombre' => $m->estado?->nombre,
                'tipo_nombre' => $m->tipo?->nombre,
                'prioridad_nombre' => $m->prioridad?->nombre,
                'sucursal_nombre' => $m->sucursal?->nombre,
                'tecnicos' => $m->tecnicos->pluck('nombre')->implode(', ') ?: null,
                'personas' => $m->tecnicos->pluck('nombre')->values(),
                'prioridad_color' => $m->prioridad?->color,
                'descripcion' => $m->problema_reportado,
                'origen' => $m->solicitud
                    ? "Solicitud {$m->solicitud->folio}"
                    : ($m->plan ? "Plan preventivo: {$m->plan->nombre}" : 'Orden directa'),
            ]);

        $preventivos = OcurrenciaPlanMantenimiento::query()
            ->with(['plan.equipo:id,codigo_activo,descripcion', 'plan.ubicacion:id,nombre', 'plan.sucursal:id,nombre', 'plan.tipo:id,nombre', 'plan.tecnico:id,nombre', 'plan.prioridad:id,nombre,color'])
            ->whereBetween('fecha_programada', [$desde->toDateString(), $hasta->toDateString()])
            ->whereNull('mantenimiento_id')
            ->when($sucursalId, fn (Builder $q, $v) => $q->whereHas('plan', fn (Builder $p) => $p->where('sucursal_id', $v)))
            ->get()
            ->map(fn (OcurrenciaPlanMantenimiento $o) => [
                'tipo_evento' => 'ocurrencia',
                'id' => $o->id,
                'plan_id' => $o->plan_id,
                'titulo' => 'Preventivo · '.($o->plan?->equipo?->codigo_activo ?? $o->plan?->ubicacion?->nombre ?? ''),
                'objetivo' => $o->plan?->equipo ? "{$o->plan->equipo->codigo_activo} — {$o->plan->equipo->descripcion}" : $o->plan?->ubicacion?->nombre,
                'objetivo_tipo' => $o->plan?->equipo ? 'equipo' : ($o->plan?->ubicacion ? 'ubicacion' : null),
                'nombre' => $o->plan?->equipo?->descripcion ?? $o->plan?->ubicacion?->nombre ?? 'Preventivo',
                'lugar' => $o->plan?->ubicacion?->nombre ?? $o->plan?->sucursal?->nombre,
                'inicio' => $o->fecha_programada,
                'fin' => null,
                'categoria' => 'preventivo',
                'color' => null,
                'estado' => $o->estado,
                'estado_nombre' => 'Programado',
                'tipo_nombre' => $o->plan?->tipo?->nombre,
                'prioridad_nombre' => $o->plan?->prioridad?->nombre,
                'prioridad_color' => $o->plan?->prioridad?->color,
                'sucursal_nombre' => $o->plan?->sucursal?->nombre,
                'tecnicos' => $o->plan?->tecnico?->nombre,
                'personas' => collect([$o->plan?->tecnico?->nombre])->filter()->values(),
                'origen' => $o->plan ? "Plan preventivo: {$o->plan->nombre}" : 'Plan preventivo',
            ]);

        // Las tareas administrativas también se muestran en el calendario por su
        // fecha límite (Propuesta técnica — anexo "TAREAS"). El técnico y el
        // usuario básico solo ven las que tienen asignadas, igual que en el
        // listado de Tareas.q
        $tareas = Tarea::query()
            ->with([
                'prioridad:id,nombre,color',
                'proyecto:id,nombre',
                'categoriaTarea:id,nombre',
                'responsables' => fn ($q) => $q->wherePivotNull('desasignado_at')->select('usuarios.id', 'usuarios.nombre'),
            ])
            ->whereBetween('fecha_limite', [$desde->toDateString(), $hasta->toDateString()])
            ->when(
                ! $request->user()->hasAnyRole(['superadministrador', 'supervisor', 'auditor']),
                fn (Builder $q) => $q->whereHas('responsables', fn (Builder $r) => $r
                    ->where('usuarios.id', $request->user()->id)->whereNull('tarea_responsables.desasignado_at')),
            )
            ->get()
            ->map(fn (Tarea $t) => [
                'tipo_evento' => 'tarea',
                'id' => $t->id,
                'titulo' => 'Tarea · '.Str::limit($t->titulo ?: $t->descripcion, 40),
                'nombre' => $t->titulo ?: Str::limit($t->descripcion, 60),
                'lugar' => null,
                'descripcion' => $t->descripcion,
                'inicio' => $t->fecha_limite,
                'fin' => null,
                'categoria' => 'tarea',
                'color' => $t->prioridad?->color,
                'estado' => $t->estado,
                'estado_nombre' => CicloTarea::etiqueta($t->estado),
                'prioridad_nombre' => $t->prioridad?->nombre,
                'prioridad_color' => $t->prioridad?->color,
                'responsables' => $t->responsables->pluck('nombre')->implode(', ') ?: null,
                'personas' => $t->responsables->pluck('nombre')->values(),
                'clasificacion' => $t->clasificacion ?: 'general',
                'clasificacion_nombre' => match ($t->clasificacion) {
                    'proyecto' => $t->proyecto?->nombre,
                    'categoria' => $t->categoriaTarea?->nombre,
                    default => null,
                },
            ]);

        return Inertia::render('Mantenimiento/Calendario/Index', [
            'eventos' => $ordenes->concat($preventivos)->concat($tareas)->values(),
            'rango' => ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()],
            'sucursalId' => $sucursalId,
            'puedeVerTodas' => $request->user()->puedeVerTodasLasSucursales(),
            'filtros' => $request->only(['tipo_id', 'prioridad_id', 'estado_id', 'tecnico_id']),
            'catalogos' => [
                'sucursales' => $request->user()->sucursalesPermitidas(),
                'tecnicos' => Usuario::role('tecnico')->where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']),
                'tipos' => TipoMantenimiento::orderBy('nombre')->get(['id', 'nombre']),
                'prioridades' => Prioridad::orderBy('nivel')->get(['id', 'nombre', 'color']),
                'estados' => EstadoMantenimiento::orderBy('orden')->get(['id', 'nombre']),
            ],
            // Catálogos de los modales de "Agendar nueva actividad"; solo se
            // envían cuando la vista los pide (al abrir un modal).
            'formularios' => Inertia::optional(fn () => $this->formularios($request, $sucursalId)),
        ]);
    }

    private function formularios(Request $request, ?int $sucursalId): array
    {
        $usuario = $request->user();
        $conObjetivos = $usuario->can('mantenimientos.crear') || $usuario->can('solicitudes.crear');
        $conTareas = $usuario->can('tareas.crear');

        return [
            'equipos' => $conObjetivos
                ? Equipo::when($sucursalId, fn ($q, $v) => $q->where('sucursal_id', $v))->orderBy('codigo_activo')->get(['id', 'codigo_activo', 'descripcion'])
                : [],
            'ubicaciones' => $conObjetivos
                ? Ubicacion::activos()->when($sucursalId, fn ($q, $v) => $q->where('sucursal_id', $v))->orderBy('ruta')->get(['id', 'nombre', 'profundidad', 'sucursal_id'])
                : [],
            'tipos' => TipoMantenimiento::activos()->orderBy('nombre')->get(['id', 'nombre', 'categoria']),
            'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre', 'color']),
            'usuarios' => $conTareas ? Usuario::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre', 'apellidos']) : [],
            'proyectos' => $conTareas ? Proyecto::activos()->orderBy('nombre')->get(['id', 'nombre']) : [],
            'categorias' => $conTareas ? CategoriaTarea::activos()->orderBy('nombre')->get(['id', 'nombre']) : [],
        ];
    }
}
