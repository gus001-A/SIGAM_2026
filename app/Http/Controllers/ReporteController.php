<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\EstadoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\PlanMantenimiento;
use App\Models\Prioridad;
use App\Models\SolicitudMantenimiento;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use App\Models\TipoMantenimiento;
use App\Models\Usuario;
use App\Support\Marca;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Catálogo de reportes. Especificación v2.0 §10-§11.
 * Cada reporte devuelve { columnas, filas, totales } para vista previa y exportación.
 */
class ReporteController extends Controller
{
    /** Definición de los 16 reportes de la especificación (§10). */
    public const REPORTES = [
        'inventario_general' => 'RPT-01 Inventario general',
        'inventario_por_sucursal' => 'RPT-02 Inventario por sucursal',
        'ficha_equipo' => 'RPT-03 Ficha / historial de equipo',
        'mantenimientos_por_periodo' => 'RPT-04 Mantenimientos por periodo',
        'preventivos_proximos' => 'RPT-05 Preventivos próximos',
        'preventivos_vencidos' => 'RPT-06 Preventivos vencidos',
        'correctivos' => 'RPT-07 Correctivos',
        'urgencias' => 'RPT-08 Urgencias',
        'productividad_tecnico' => 'RPT-09 Productividad por técnico',
        'solicitudes_por_usuario' => 'RPT-10 Solicitudes por usuario',
        'cumplimiento_preventivo' => 'RPT-11 Cumplimiento preventivo',
        'costos_mantenimiento' => 'RPT-12 Costos de mantenimiento',
        'documentos_activos' => 'RPT-13 Documentos de activos',
        'auditoria' => 'RPT-14 Auditoría',
        'catalogos_trazabilidad' => 'RPT-15 Catálogos y trazabilidad',
        'indicadores_ejecutivos' => 'RPT-16 Indicadores ejecutivos',
        'cumplimiento_tareas' => 'RPT-17 Cumplimiento de tareas',
    ];

    /** Agrupación para el catálogo de reportes (§10). */
    public const GRUPOS = [
        'Inventario' => ['inventario_general', 'inventario_por_sucursal', 'ficha_equipo', 'documentos_activos', 'catalogos_trazabilidad'],
        'Mantenimiento' => ['mantenimientos_por_periodo', 'preventivos_proximos', 'preventivos_vencidos', 'correctivos', 'urgencias', 'cumplimiento_preventivo'],
        'Desempeño y costos' => ['productividad_tecnico', 'solicitudes_por_usuario', 'costos_mantenimiento', 'indicadores_ejecutivos', 'cumplimiento_tareas'],
        'Control' => ['auditoria'],
    ];

    /** Filtros que acepta cada reporte (para armar el formulario en la vista). */
    public const FILTROS = [
        'inventario_general' => ['sucursal_id', 'tipo_equipo_id', 'estado_equipo_id'],
        'inventario_por_sucursal' => ['tipo_equipo_id'],
        'mantenimientos_por_periodo' => ['desde', 'hasta', 'sucursal_id', 'tipo_mant_id', 'estado_mant_id'],
        'preventivos_proximos' => ['dias', 'sucursal_id'],
        'preventivos_vencidos' => [],
        'correctivos' => ['desde', 'hasta', 'sucursal_id'],
        'urgencias' => [],
        'productividad_tecnico' => ['desde', 'hasta'],
        'documentos_activos' => ['sucursal_id'],
        'costos_mantenimiento' => ['desde', 'hasta', 'sucursal_id'],
        'solicitudes_por_usuario' => [],
        'cumplimiento_tareas' => ['desde', 'hasta'],
    ];

    public function index(): Response
    {
        $this->authorize('reportes.ver');

        $implementados = array_keys(self::FILTROS);

        // Solo se listan los reportes ya disponibles (sin "próximamente").
        $grupos = collect(self::GRUPOS)->map(fn (array $claves, string $grupo) => [
            'grupo' => $grupo,
            'reportes' => collect($claves)
                ->filter(fn (string $c) => in_array($c, $implementados, true))
                ->map(fn (string $c) => ['clave' => $c, 'nombre' => self::REPORTES[$c]])
                ->values(),
        ])->filter(fn (array $g) => $g['reportes']->isNotEmpty())->values();

        return Inertia::render('Reportes/Index', ['grupos' => $grupos]);
    }

    public function generar(Request $request, string $clave): Response
    {
        $this->authorize('reportes.ver');
        abort_unless(array_key_exists($clave, self::REPORTES), 404);

        $resultado = $this->construir($clave, $request);

        return Inertia::render('Reportes/Ver', [
            'clave' => $clave,
            'nombre' => self::REPORTES[$clave],
            'filtros' => $request->except('page'),
            'filtrosDisponibles' => self::FILTROS[$clave] ?? [],
            'catalogos' => $this->catalogos(),
            'puedeExportar' => (bool) $request->user()?->can('reportes.exportar'),
            'generado_por' => $request->user()->only(['id', 'nombre']),
            'generado_at' => now(),
            ...$resultado,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogos(): array
    {
        return [
            'sucursales' => Sucursal::orderBy('nombre')->get(['id', 'nombre']),
            'tipos_equipo' => TipoEquipo::orderBy('nombre')->get(['id', 'nombre']),
            'estados_equipo' => EstadoEquipo::orderBy('nombre')->get(['id', 'nombre']),
            'tipos_mant' => TipoMantenimiento::orderBy('nombre')->get(['id', 'nombre']),
            'estados_mant' => EstadoMantenimiento::orderBy('orden')->get(['id', 'nombre']),
            'prioridades' => Prioridad::orderBy('nivel')->get(['id', 'nombre']),
        ];
    }

    public function exportar(Request $request, string $clave): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('reportes.exportar');
        abort_unless(array_key_exists($clave, self::REPORTES), 404);

        $formato = $request->query('formato', 'xlsx');
        abort_unless(in_array($formato, ['xlsx', 'pdf'], true), 422, 'Formato no admitido.');

        ['columnas' => $columnas, 'filas' => $filas, 'totales' => $totales] = [
            'totales' => [],
            ...$this->construir($clave, $request),
        ];
        $filas = collect($filas)->map(fn ($f) => array_values((array) $f))->all();
        $base = Str::slug($clave).'-'.now()->format('Ymd-His');

        return match ($formato) {
            'xlsx' => $this->exportarXlsx($columnas, $filas, "{$base}.xlsx"),
            'pdf' => $this->exportarPdf($clave, $columnas, $filas, $totales, "{$base}.pdf", $request),
        };
    }

    /**
     * @param  list<string>  $columnas
     * @param  list<array<int, mixed>>  $filas
     */
    private function exportarXlsx(array $columnas, array $filas, string $nombre): BinaryFileResponse
    {
        $ruta = tempnam(sys_get_temp_dir(), 'rpt').'.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($ruta);
        $writer->addRow(Row::fromValues($columnas));
        foreach ($filas as $fila) {
            $writer->addRow(Row::fromValues(array_map(
                fn ($v) => is_scalar($v) || $v === null ? $v : (string) $v,
                $fila,
            )));
        }
        $writer->close();

        return response()->download($ruta, $nombre)->deleteFileAfterSend();
    }

    /**
     * @param  list<string>  $columnas
     * @param  list<array<int, mixed>>  $filas
     * @param  array<string, mixed>  $totales
     */
    private function exportarPdf(string $clave, array $columnas, array $filas, array $totales, string $nombre, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = Pdf::loadView('pdf.reporte', [
            'titulo' => self::REPORTES[$clave],
            'columnas' => $columnas,
            'filas' => $filas,
            'totales' => $totales,
            'logo' => Marca::logoDataUri(),
            'generado_por' => $request->user()?->nombre,
            'generado_at' => now(),
        ])->setPaper('a4', count($columnas) > 6 ? 'landscape' : 'portrait');

        return $pdf->download($nombre);
    }

    /**
     * @return array{columnas: list<string>, filas: iterable<int, mixed>, totales?: array<string, mixed>, pendiente?: bool}
     */
    private function construir(string $clave, Request $request): array
    {
        return match ($clave) {
            'inventario_general' => $this->inventarioGeneral($request),
            'inventario_por_sucursal' => $this->inventarioPorSucursal($request),
            'mantenimientos_por_periodo' => $this->mantenimientosPorPeriodo($request),
            'preventivos_proximos' => $this->preventivosProximos($request),
            'preventivos_vencidos' => $this->preventivosVencidos(),
            'correctivos' => $this->correctivos($request),
            'urgencias' => $this->urgencias(),
            'productividad_tecnico' => $this->productividadTecnico($request),
            'documentos_activos' => $this->documentosActivos($request),
            'solicitudes_por_usuario' => $this->solicitudesPorUsuario($request),
            'costos_mantenimiento' => $this->costosMantenimiento($request),
            'cumplimiento_tareas' => $this->cumplimientoTareas($request),
            default => ['columnas' => [], 'filas' => [], 'pendiente' => true],
        };
    }

    private function inventarioGeneral(Request $request): array
    {
        $equipos = Equipo::query()
            ->with(['tipo:id,nombre', 'marca:id,nombre', 'sucursal:id,nombre', 'ubicacion:id,nombre', 'estado:id,nombre'])
            ->when($request->integer('sucursal_id'), fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->when($request->integer('tipo_equipo_id'), fn (Builder $q, $v) => $q->where('tipo_id', $v))
            ->when($request->integer('estado_equipo_id'), fn (Builder $q, $v) => $q->where('estado_id', $v))
            ->orderBy('codigo_activo')
            ->get();

        return [
            'columnas' => ['Código', 'Descripción', 'Tipo', 'Marca', 'Serie', 'Sucursal', 'Ubicación', 'Estado', 'Valor'],
            'filas' => $equipos->map(fn (Equipo $e) => [
                $e->codigo_activo, $e->descripcion, $e->tipo?->nombre, $e->marca?->nombre, $e->numero_serie,
                $e->sucursal?->nombre, $e->ubicacion?->nombre, $e->estado?->nombre, $e->valor_adquisicion,
            ]),
            'totales' => ['cantidad' => $equipos->count(), 'valor' => (float) $equipos->sum('valor_adquisicion')],
        ];
    }

    private function mantenimientosPorPeriodo(Request $request): array
    {
        $desde = Carbon::parse($request->query('desde', now()->startOfMonth()->toDateString()));
        $hasta = Carbon::parse($request->query('hasta', now()->toDateString()))->endOfDay();

        $ordenes = Mantenimiento::query()
            ->with(['equipo:id,codigo_activo', 'ubicacion:id,nombre', 'tipo:id,nombre', 'estado:id,nombre', 'prioridad:id,nombre', 'tecnicos:id,nombre'])
            ->whereBetween('created_at', [$desde, $hasta])
            ->when($request->integer('sucursal_id'), fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->when($request->integer('tipo_mant_id'), fn (Builder $q, $v) => $q->where('tipo_id', $v))
            ->when($request->integer('estado_mant_id'), fn (Builder $q, $v) => $q->where('estado_id', $v))
            ->orderBy('created_at')
            ->get();

        return [
            'columnas' => ['Folio', 'Equipo / instalación', 'Tipo', 'Técnico(s)', 'Programado', 'Completado', 'Prioridad', 'Estado'],
            'filas' => $ordenes->map(fn (Mantenimiento $m) => [
                $m->folio, $m->equipo?->codigo_activo ?? $m->ubicacion?->nombre, $m->tipo?->nombre,
                $m->tecnicos->pluck('nombre')->implode(', '),
                optional($m->programado_inicio)->format('Y-m-d'),
                optional($m->completado_at)->format('Y-m-d'),
                $m->prioridad?->nombre, $m->estado?->nombre,
            ]),
            'totales' => ['ordenes' => $ordenes->count()],
        ];
    }

    private function preventivosVencidos(): array
    {
        $planes = PlanMantenimiento::query()
            ->with(['equipo:id,codigo_activo,descripcion', 'ubicacion:id,nombre', 'tecnico:id,nombre'])
            ->where('estado', 'activo')
            ->whereDate('proxima_fecha', '<', today())
            ->orderBy('proxima_fecha')
            ->get();

        return [
            'columnas' => ['Equipo / instalación', 'Descripción', 'Próxima fecha', 'Días vencidos', 'Técnico'],
            'filas' => $planes->map(fn (PlanMantenimiento $p) => [
                $p->equipo?->codigo_activo ?? $p->ubicacion?->nombre, $p->equipo?->descripcion,
                optional($p->proxima_fecha)->format('Y-m-d'),
                $p->proxima_fecha ? today()->diffInDays(Carbon::parse($p->proxima_fecha)) : null,
                $p->tecnico?->nombre,
            ]),
            'totales' => ['planes' => $planes->count()],
        ];
    }

    private function urgencias(): array
    {
        $ordenes = Mantenimiento::query()
            ->with(['equipo:id,codigo_activo', 'ubicacion:id,nombre', 'estado:id,nombre', 'prioridad:id,nombre', 'tecnicos:id,nombre'])
            ->whereHas('prioridad', fn (Builder $q) => $q->whereIn('clave', ['urgente', 'critica']))
            ->whereHas('estado', fn (Builder $q) => $q->where('es_abierto', true))
            ->orderBy('programado_inicio')
            ->get();

        return [
            'columnas' => ['Folio', 'Equipo / instalación', 'Prioridad', 'Programado', 'Técnico(s)', 'Estado'],
            'filas' => $ordenes->map(fn (Mantenimiento $m) => [
                $m->folio, $m->equipo?->codigo_activo ?? $m->ubicacion?->nombre, $m->prioridad?->nombre,
                optional($m->programado_inicio)->format('Y-m-d H:i'),
                $m->tecnicos->pluck('nombre')->implode(', '), $m->estado?->nombre,
            ]),
            'totales' => ['urgencias' => $ordenes->count()],
        ];
    }

    private function solicitudesPorUsuario(Request $request): array
    {
        $filas = SolicitudMantenimiento::query()
            ->selectRaw('solicitado_por, count(*) as total')
            ->selectRaw('sum(case when estados_mantenimiento.es_abierto = 1 then 1 else 0 end) as abiertas')
            ->join('estados_mantenimiento', 'estados_mantenimiento.id', '=', 'solicitudes_mantenimiento.estado_id')
            ->with('solicitante:id,nombre')
            ->groupBy('solicitado_por')
            ->get();

        return [
            'columnas' => ['Usuario', 'Total', 'Abiertas'],
            'filas' => $filas->map(fn ($f) => [$f->solicitante?->nombre, $f->total, $f->abiertas]),
        ];
    }

    private function costosMantenimiento(Request $request): array
    {
        $ordenes = Mantenimiento::query()
            ->with(['equipo:id,codigo_activo', 'ubicacion:id,nombre'])
            ->withSum('materiales as costo_materiales', 'costo_unitario')
            ->when($request->integer('sucursal_id'), fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->when($request->filled('desde'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->date('hasta')))
            ->get();

        return [
            'columnas' => ['Folio', 'Equipo / instalación', 'Mano de obra', 'Materiales', 'Otros', 'Total'],
            'filas' => $ordenes->map(function (Mantenimiento $m) {
                $total = (float) $m->costo_mano_obra + (float) $m->costo_materiales + (float) $m->costo_otros;

                return [$m->folio, $m->equipo?->codigo_activo ?? $m->ubicacion?->nombre, $m->costo_mano_obra, $m->costo_materiales, $m->costo_otros, $total];
            }),
            'totales' => [
                'mano_obra' => (float) $ordenes->sum('costo_mano_obra'),
                'materiales' => (float) $ordenes->sum('costo_materiales'),
                'otros' => (float) $ordenes->sum('costo_otros'),
            ],
        ];
    }

    /** Cumplimiento de tareas por responsable: a tiempo, retrasadas, canceladas. */
    private function cumplimientoTareas(Request $request): array
    {
        $desde = Carbon::parse($request->query('desde', now()->startOfMonth()->toDateString()));
        $hasta = Carbon::parse($request->query('hasta', now()->toDateString()))->endOfDay();

        $usuarios = Usuario::query()
            ->withCount([
                'tareasAsignadas as asignadas' => fn (Builder $q) => $q->whereBetween('tareas.created_at', [$desde, $hasta]),
                'tareasAsignadas as completadas' => fn (Builder $q) => $q
                    ->whereBetween('tareas.created_at', [$desde, $hasta])->where('tareas.estado', 'realizada'),
                'tareasAsignadas as a_tiempo' => fn (Builder $q) => $q
                    ->whereBetween('tareas.created_at', [$desde, $hasta])->where('tareas.estado', 'realizada')
                    ->whereRaw('date(tareas.realizada_at) <= tareas.fecha_limite'),
                'tareasAsignadas as canceladas' => fn (Builder $q) => $q
                    ->whereBetween('tareas.created_at', [$desde, $hasta])->where('tareas.estado', 'cancelada'),
            ])
            ->having('asignadas', '>', 0)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'apellidos']);

        return [
            'columnas' => ['Responsable', 'Asignadas', 'Completadas', 'A tiempo', 'Retrasadas', 'Canceladas', '% cumplimiento'],
            'filas' => $usuarios->map(function (Usuario $u) {
                $retrasadas = $u->completadas - $u->a_tiempo;
                $pct = $u->asignadas > 0 ? round($u->a_tiempo / $u->asignadas * 100, 1) : 0.0;

                return [$u->nombre_completo, $u->asignadas, $u->completadas, $u->a_tiempo, $retrasadas, $u->canceladas, $pct];
            }),
            'totales' => [
                'responsables' => $usuarios->count(),
                'asignadas' => (int) $usuarios->sum('asignadas'),
                'completadas' => (int) $usuarios->sum('completadas'),
            ],
        ];
    }

    private function inventarioPorSucursal(Request $request): array
    {
        $filas = Sucursal::query()
            ->withCount('equipos')
            ->withSum('equipos as valor', 'valor_adquisicion')
            ->when($request->integer('tipo_equipo_id'), fn (Builder $q, $v) => $q
                ->whereHas('equipos', fn (Builder $e) => $e->where('tipo_id', $v)))
            ->orderBy('nombre')
            ->get();

        return [
            'columnas' => ['Sucursal', 'Equipos', 'Valor de activos'],
            'filas' => $filas->map(fn (Sucursal $s) => [$s->nombre, $s->equipos_count, (float) $s->valor]),
            'totales' => [
                'sucursales' => $filas->count(),
                'equipos' => (int) $filas->sum('equipos_count'),
                'valor' => (float) $filas->sum('valor'),
            ],
        ];
    }

    private function preventivosProximos(Request $request): array
    {
        $dias = max(1, min(365, $request->integer('dias', 30)));
        $limite = today()->addDays($dias);

        $planes = PlanMantenimiento::query()
            ->with(['equipo:id,codigo_activo,descripcion', 'ubicacion:id,nombre', 'sucursal:id,nombre', 'tecnico:id,nombre'])
            ->where('estado', 'activo')
            ->whereNotNull('proxima_fecha')
            ->whereDate('proxima_fecha', '>=', today())
            ->whereDate('proxima_fecha', '<=', $limite)
            ->when($request->integer('sucursal_id'), fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->orderBy('proxima_fecha')
            ->get();

        return [
            'columnas' => ['Equipo / instalación', 'Descripción', 'Sucursal', 'Próxima fecha', 'Días restantes', 'Técnico'],
            'filas' => $planes->map(fn (PlanMantenimiento $p) => [
                $p->equipo?->codigo_activo ?? $p->ubicacion?->nombre,
                $p->equipo?->descripcion,
                $p->sucursal?->nombre,
                optional($p->proxima_fecha)->format('Y-m-d'),
                $p->proxima_fecha ? (int) round(today()->diffInDays(Carbon::parse($p->proxima_fecha), false)) : null,
                $p->tecnico?->nombre,
            ]),
            'totales' => ['planes' => $planes->count(), 'ventana_dias' => $dias],
        ];
    }

    private function correctivos(Request $request): array
    {
        $desde = Carbon::parse($request->query('desde', now()->startOfMonth()->toDateString()));
        $hasta = Carbon::parse($request->query('hasta', now()->toDateString()))->endOfDay();

        $ordenes = Mantenimiento::query()
            ->with(['equipo:id,codigo_activo', 'ubicacion:id,nombre', 'sucursal:id,nombre', 'estado:id,nombre', 'tecnicos:id,nombre'])
            ->whereHas('tipo', fn (Builder $q) => $q->where('categoria', 'correctivo'))
            ->whereBetween('created_at', [$desde, $hasta])
            ->when($request->integer('sucursal_id'), fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->orderBy('created_at')
            ->get();

        return [
            'columnas' => ['Folio', 'Equipo / instalación', 'Sucursal', 'Reportado', 'Completado', 'Técnico(s)', 'Estado'],
            'filas' => $ordenes->map(fn (Mantenimiento $m) => [
                $m->folio, $m->equipo?->codigo_activo ?? $m->ubicacion?->nombre, $m->sucursal?->nombre,
                optional($m->created_at)->format('Y-m-d'),
                optional($m->completado_at)->format('Y-m-d'),
                $m->tecnicos->pluck('nombre')->implode(', '),
                $m->estado?->nombre,
            ]),
            'totales' => ['correctivos' => $ordenes->count()],
        ];
    }

    private function productividadTecnico(Request $request): array
    {
        $desde = Carbon::parse($request->query('desde', now()->startOfMonth()->toDateString()));
        $hasta = Carbon::parse($request->query('hasta', now()->toDateString()))->endOfDay();

        $tecnicos = Usuario::query()
            ->role('tecnico')
            ->withCount([
                'mantenimientosAsignados as asignadas' => fn (Builder $q) => $q->whereBetween('mantenimientos.created_at', [$desde, $hasta]),
                'mantenimientosAsignados as completadas' => fn (Builder $q) => $q
                    ->whereBetween('mantenimientos.created_at', [$desde, $hasta])
                    ->whereNotNull('completado_at'),
            ])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'apellidos']);

        return [
            'columnas' => ['Técnico', 'Órdenes asignadas', 'Completadas', '% cumplimiento'],
            'filas' => $tecnicos->map(function (Usuario $u) {
                $pct = $u->asignadas > 0 ? round($u->completadas / $u->asignadas * 100, 1) : 0.0;

                return [$u->nombre_completo, $u->asignadas, $u->completadas, $pct];
            }),
            'totales' => [
                'tecnicos' => $tecnicos->count(),
                'asignadas' => (int) $tecnicos->sum('asignadas'),
                'completadas' => (int) $tecnicos->sum('completadas'),
            ],
        ];
    }

    private function documentosActivos(Request $request): array
    {
        $docs = Documento::query()
            ->join('documento_relacionado as dr', 'dr.documento_id', '=', 'documentos.id')
            ->join('equipos', fn ($j) => $j->on('equipos.id', '=', 'dr.relacionado_id')->where('dr.relacionado_type', Equipo::class))
            ->when($request->integer('sucursal_id'), fn (Builder $q, $v) => $q->where('equipos.sucursal_id', $v))
            ->orderBy('equipos.codigo_activo')
            ->get([
                'equipos.codigo_activo', 'documentos.nombre_original', 'documentos.categoria',
                'dr.rol', 'documentos.tamano', 'documentos.vence_at', 'documentos.created_at',
            ]);

        return [
            'columnas' => ['Equipo', 'Documento', 'Categoría', 'Rol', 'Tamaño (KB)', 'Vence', 'Cargado'],
            'filas' => $docs->map(fn ($d) => [
                $d->codigo_activo, $d->nombre_original, $d->categoria, $d->rol,
                $d->tamano ? (int) round($d->tamano / 1024) : null,
                $d->vence_at ? Carbon::parse($d->vence_at)->format('Y-m-d') : null,
                $d->created_at ? Carbon::parse($d->created_at)->format('Y-m-d') : null,
            ]),
            'totales' => ['documentos' => $docs->count()],
        ];
    }
}
