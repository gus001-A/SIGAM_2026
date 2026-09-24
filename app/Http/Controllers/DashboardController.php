<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\EstadoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\OcurrenciaPlanMantenimiento;
use App\Models\PlanMantenimiento;
use App\Models\SolicitudMantenimiento;
use App\Models\Tarea;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Tablero de indicadores. Especificación v2.0 §5.2 / §12.
 *
 * Observación del cliente: "entre más información se anexa, más lento se
 * hace el sistema para cargar la ventana". Las tarjetas y listas ya solo
 * traen columnas seleccionadas y `limit(10)`, así que el costo real crece
 * con el tamaño del inventario/histórico, no con fugas N+1. Se cachea el
 * resultado completo por 60s — el panel no necesita ser exacto al segundo
 * y así una recarga o varias personas viéndolo al mismo tiempo no vuelven
 * a pagar el costo de las mismas consultas.
 */
class DashboardController extends Controller
{
    private const CACHE_SEGUNDOS = 60;

    private const MAXIMO_POR_PANEL = 6;

    public function index(Request $request): Response
    {
        $this->authorize('dashboard.ver');

        $usuario = $request->user();
        // Las tareas solo las ve por completo el superadministrador; el resto
        // solo ve las suyas (creadas o asignadas), así que el caché no puede
        // ser una sola clave global compartida entre todos los usuarios.
        $clave = 'dashboard:datos:'.($usuario->hasRole('superadministrador') ? 'admin' : 'u'.$usuario->id);

        return Inertia::render('Dashboard', Cache::remember(
            $clave,
            self::CACHE_SEGUNDOS,
            fn () => $this->calcular($usuario),
        ));
    }

    /** @return array<string, mixed> */
    private function calcular(Usuario $usuario): array
    {
        $abiertos = EstadoMantenimiento::where('es_abierto', true)->pluck('id');
        $prioridadesUrgentes = ['urgente', 'critica'];

        $soloMisTareas = fn (Builder $q) => $usuario->hasRole('superadministrador')
            ? $q
            : $q->where(fn (Builder $w) => $w
                ->where('creado_por', $usuario->id)
                ->orWhereHas('responsables', fn (Builder $r) => $r
                    ->where('usuarios.id', $usuario->id)->whereNull('tarea_responsables.desasignado_at')));

        return [
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
                'tareas_pendientes' => Tarea::whereIn('estado', ['pendiente', 'en_proceso'])->tap($soloMisTareas)->count(),
                'tareas_vencidas' => Tarea::whereIn('estado', ['pendiente', 'en_proceso'])->whereDate('fecha_limite', '<', today())->tap($soloMisTareas)->count(),
            ],
            'agenda' => Mantenimiento::query()
                ->with(['equipo:id,codigo_activo', 'ubicacion:id,nombre', 'tipo:id,nombre', 'prioridad:id,nombre,color', 'estado:id,nombre'])
                ->whereIn('estado_id', $abiertos)
                ->whereNotNull('programado_inicio')
                ->orderBy('programado_inicio')
                ->limit(self::MAXIMO_POR_PANEL)
                ->get(['id', 'folio', 'equipo_id', 'ubicacion_id', 'tipo_id', 'prioridad_id', 'estado_id', 'programado_inicio']),
            'urgencias' => Mantenimiento::query()
                ->with(['equipo:id,codigo_activo', 'ubicacion:id,nombre', 'estado:id,nombre'])
                ->whereIn('estado_id', $abiertos)
                ->whereHas('prioridad', fn ($q) => $q->whereIn('clave', $prioridadesUrgentes))
                ->latest('id')
                ->limit(self::MAXIMO_POR_PANEL)
                ->get(['id', 'folio', 'equipo_id', 'ubicacion_id', 'estado_id', 'programado_inicio']),
            'preventivos_proximos' => OcurrenciaPlanMantenimiento::query()
                ->with([
                    'plan.equipo:id,codigo_activo',
                    'plan.ubicacion:id,nombre,codigo',
                    'plan.sucursal:id,nombre',
                    'plan.tipo:id,nombre',
                ])
                ->whereNull('mantenimiento_id')
                ->whereDate('fecha_programada', '>=', today())
                ->orderBy('fecha_programada')
                ->limit(self::MAXIMO_POR_PANEL)
                ->get(),
            'tareas_proximas' => Tarea::query()
                ->with([
                    'prioridad:id,nombre,color',
                    'responsables' => fn ($q) => $q->wherePivotNull('desasignado_at')->select('usuarios.id', 'usuarios.nombre'),
                ])
                ->whereIn('estado', ['pendiente', 'en_proceso'])
                ->tap($soloMisTareas)
                ->orderBy('fecha_limite')
                ->limit(self::MAXIMO_POR_PANEL)
                ->get(['id', 'titulo', 'descripcion', 'fecha_limite', 'estado', 'prioridad_id']),
        ];
    }
}
