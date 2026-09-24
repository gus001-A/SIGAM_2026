<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use App\Models\EstadoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\OcurrenciaPlanMantenimiento;
use App\Models\Prioridad;
use App\Models\Sucursal;
use App\Models\Tarea;
use App\Models\TipoMantenimiento;
use App\Models\Usuario;
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
        $sucursalId = SeleccionSucursal::resolver($request->query('sucursal_id'));

        $ordenes = Mantenimiento::query()
            ->with(['equipo:id,codigo_activo,descripcion', 'ubicacion:id,nombre', 'sucursal:id,nombre', 'tipo:id,nombre,categoria', 'prioridad:id,nombre,color', 'estado:id,nombre,clave', 'tecnicos:id,nombre'])
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
            ]);

        $preventivos = OcurrenciaPlanMantenimiento::query()
            ->with(['plan.equipo:id,codigo_activo,descripcion', 'plan.ubicacion:id,nombre', 'plan.sucursal:id,nombre', 'plan.tipo:id,nombre'])
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
                'inicio' => $o->fecha_programada,
                'fin' => null,
                'categoria' => 'preventivo',
                'color' => null,
                'estado' => $o->estado,
                'tipo_nombre' => $o->plan?->tipo?->nombre,
                'sucursal_nombre' => $o->plan?->sucursal?->nombre,
            ]);

        // Las tareas administrativas también se muestran en el calendario por su
        // fecha límite (Propuesta técnica — anexo "TAREAS"). El técnico y el
        // usuario básico solo ven las que tienen asignadas, igual que en el
        // listado de Tareas.
        $tareas = Tarea::query()
            ->with([
                'prioridad:id,nombre,color',
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
                'titulo' => 'Tarea · '.Str::limit($t->descripcion, 40),
                'descripcion' => $t->descripcion,
                'inicio' => $t->fecha_limite,
                'fin' => null,
                'categoria' => 'tarea',
                'color' => $t->prioridad?->color,
                'estado' => $t->estado,
                'prioridad_nombre' => $t->prioridad?->nombre,
                'responsables' => $t->responsables->pluck('nombre')->implode(', ') ?: null,
            ]);

        return Inertia::render('Mantenimiento/Calendario/Index', [
            'eventos' => $ordenes->concat($preventivos)->concat($tareas)->values(),
            'rango' => ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()],
            'sucursalId' => $sucursalId,
            'filtros' => $request->only(['tipo_id', 'prioridad_id', 'estado_id', 'tecnico_id']),
            'catalogos' => [
                'sucursales' => Sucursal::activos()->orderBy('nombre')->get(['id', 'nombre']),
                'tecnicos' => Usuario::role('tecnico')->where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']),
                'tipos' => TipoMantenimiento::orderBy('nombre')->get(['id', 'nombre']),
                'prioridades' => Prioridad::orderBy('nivel')->get(['id', 'nombre', 'color']),
                'estados' => EstadoMantenimiento::orderBy('orden')->get(['id', 'nombre']),
            ],
        ]);
    }
}
