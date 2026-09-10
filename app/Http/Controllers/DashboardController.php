<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\EstadoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\OcurrenciaPlanMantenimiento;
use App\Models\PlanMantenimiento;
use App\Models\SolicitudMantenimiento;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tablero de indicadores. Especificación v2.0 §5.2 / §12.
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('dashboard.ver');

        $abiertos = EstadoMantenimiento::where('es_abierto', true)->pluck('id');
        $prioridadesUrgentes = ['urgente', 'critica'];

        return Inertia::render('Dashboard', [
            'tarjetas' => [
                'total_equipos' => Equipo::count(),
                'valor_inventario' => (float) Equipo::sum('valor_adquisicion'),
                'mantenimientos_pendientes' => Mantenimiento::whereIn('estado_id', $abiertos)->count(),
                'urgentes' => Mantenimiento::whereIn('estado_id', $abiertos)
                    ->whereHas('prioridad', fn ($q) => $q->whereIn('clave', $prioridadesUrgentes))
                    ->count(),
                'preventivos_vencidos' => PlanMantenimiento::whereDate('proxima_fecha', '<', today())
                    ->where('estado', 'activo')
                    ->count(),
                'solicitudes_abiertas' => SolicitudMantenimiento::whereIn('estado_id', $abiertos)->count(),
            ],
            'por_estado' => Mantenimiento::query()
                ->selectRaw('estados_mantenimiento.nombre, count(*) as total')
                ->join('estados_mantenimiento', 'estados_mantenimiento.id', '=', 'mantenimientos.estado_id')
                ->groupBy('estados_mantenimiento.nombre')
                ->pluck('total', 'nombre'),
            'agenda' => Mantenimiento::query()
                ->with(['equipo:id,codigo_activo', 'tipo:id,nombre', 'prioridad:id,nombre,color', 'estado:id,nombre'])
                ->whereIn('estado_id', $abiertos)
                ->whereNotNull('programado_inicio')
                ->orderBy('programado_inicio')
                ->limit(10)
                ->get(['id', 'folio', 'equipo_id', 'tipo_id', 'prioridad_id', 'estado_id', 'programado_inicio']),
            'urgencias' => Mantenimiento::query()
                ->with(['equipo:id,codigo_activo', 'estado:id,nombre'])
                ->whereIn('estado_id', $abiertos)
                ->whereHas('prioridad', fn ($q) => $q->whereIn('clave', $prioridadesUrgentes))
                ->latest('id')
                ->limit(10)
                ->get(['id', 'folio', 'equipo_id', 'estado_id', 'programado_inicio']),
            'preventivos_proximos' => OcurrenciaPlanMantenimiento::query()
                ->with('plan.equipo:id,codigo_activo')
                ->whereNull('mantenimiento_id')
                ->whereDate('fecha_programada', '>=', today())
                ->orderBy('fecha_programada')
                ->limit(10)
                ->get(),
        ]);
    }
}
