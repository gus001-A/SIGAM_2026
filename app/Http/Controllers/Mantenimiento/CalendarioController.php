<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use App\Models\EstadoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\OcurrenciaPlanMantenimiento;
use App\Models\Prioridad;
use App\Models\Sucursal;
use App\Models\TipoMantenimiento;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $ordenes = Mantenimiento::query()
            ->with(['equipo:id,codigo_activo', 'tipo:id,nombre,categoria', 'prioridad:id,nombre,color', 'estado:id,nombre,clave'])
            ->whereBetween('programado_inicio', [$desde, $hasta])
            ->when($request->integer('sucursal_id'), fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->when($request->integer('tipo_id'), fn (Builder $q, $v) => $q->where('tipo_id', $v))
            ->when($request->integer('prioridad_id'), fn (Builder $q, $v) => $q->where('prioridad_id', $v))
            ->when($request->integer('estado_id'), fn (Builder $q, $v) => $q->where('estado_id', $v))
            ->when($request->integer('tecnico_id'), fn (Builder $q, $v) => $q->whereHas('asignaciones', fn (Builder $a) => $a->where('tecnico_id', $v)))
            ->get()
            ->map(fn (Mantenimiento $m) => [
                'tipo_evento' => 'orden',
                'id' => $m->id,
                'folio' => $m->folio,
                'titulo' => "{$m->folio} · {$m->equipo?->codigo_activo}",
                'inicio' => $m->programado_inicio,
                'fin' => $m->programado_fin,
                'categoria' => $m->tipo?->categoria,
                'color' => $m->prioridad?->color,
                'estado' => $m->estado?->clave,
            ]);

        $preventivos = OcurrenciaPlanMantenimiento::query()
            ->with('plan.equipo:id,codigo_activo')
            ->whereBetween('fecha_programada', [$desde->toDateString(), $hasta->toDateString()])
            ->whereNull('mantenimiento_id')
            ->get()
            ->map(fn (OcurrenciaPlanMantenimiento $o) => [
                'tipo_evento' => 'ocurrencia',
                'id' => $o->id,
                'plan_id' => $o->plan_id,
                'titulo' => 'Preventivo · '.($o->plan?->equipo?->codigo_activo ?? ''),
                'inicio' => $o->fecha_programada,
                'fin' => null,
                'categoria' => 'preventivo',
                'color' => null,
                'estado' => $o->estado,
            ]);

        return Inertia::render('Mantenimiento/Calendario/Index', [
            'eventos' => $ordenes->concat($preventivos)->values(),
            'rango' => ['desde' => $desde->toDateString(), 'hasta' => $hasta->toDateString()],
            'filtros' => $request->only(['sucursal_id', 'tipo_id', 'prioridad_id', 'estado_id', 'tecnico_id']),
            'catalogos' => [
                'sucursales' => Sucursal::orderBy('nombre')->get(['id', 'nombre']),
                'tecnicos' => Usuario::role('tecnico')->where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre']),
                'tipos' => TipoMantenimiento::orderBy('nombre')->get(['id', 'nombre']),
                'prioridades' => Prioridad::orderBy('nivel')->get(['id', 'nombre', 'color']),
                'estados' => EstadoMantenimiento::orderBy('orden')->get(['id', 'nombre']),
            ],
        ]);
    }
}
