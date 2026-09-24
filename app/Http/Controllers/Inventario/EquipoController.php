<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventario\GuardarEquipoRequest;
use App\Models\Documento;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\EstadoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\Marca;
use App\Models\Norma;
use App\Models\PlanMantenimiento;
use App\Models\Proveedor;
use App\Models\SolicitudMantenimiento;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use App\Models\TipoMantenimiento;
use App\Models\Ubicacion;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\Folios;
use App\Support\Frecuencia;
use App\Support\ImportadorEquipos;
use App\Support\SeleccionSucursal;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder as QrBuilder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Inertia\Inertia;
use Inertia\Response;
use OpenSpout\Common\Entity\Row as XlsxRow;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Activos / equipos con expediente digital. Especificación v2.0 §5.7-5.9 / RF-030..036.
 */
class EquipoController extends Controller
{
    /** Columnas por las que se permite ordenar el listado. */
    private const ORDENABLES = ['codigo_activo', 'descripcion', 'numero_serie', 'valor_adquisicion', 'created_at'];

    /** Registros por página del listado de equipos. */
    private const POR_PAGINA = 15;

    /** El inventario ahora se trabaja siempre por sucursal (ver `porSucursal`). */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('equipos.por_sucursal', $request->query());
    }

    /**
     * Panorama del inventario por sucursal: comparativa de valor/cantidad
     * entre sucursales + KPIs de la sucursal seleccionada, para toma de
     * decisiones (RF de negocio: "trabajar por sucursales, como en el RIC").
     */
    public function porSucursal(Request $request): Response
    {
        $this->authorize('equipos.ver');

        $resumen = Sucursal::query()
            ->where('estado', 'activo')
            ->leftJoin('equipos', function ($j): void {
                $j->on('equipos.sucursal_id', '=', 'sucursales.id')->whereNull('equipos.deleted_at');
            })
            ->groupBy('sucursales.id', 'sucursales.codigo', 'sucursales.nombre')
            ->orderByDesc(DB::raw('COALESCE(SUM(equipos.valor_adquisicion), 0)'))
            ->get([
                'sucursales.id', 'sucursales.codigo', 'sucursales.nombre',
                DB::raw('COUNT(equipos.id) as equipos_count'),
                DB::raw('COALESCE(SUM(equipos.valor_adquisicion), 0) as valor_total'),
            ]);

        $sucursalId = SeleccionSucursal::resolver($request->query('sucursal_id'), $resumen->first()->id ?? null);
        $modoTodas = $sucursalId === null;
        $sucursal = $modoTodas || $resumen->firstWhere('id', $sucursalId);

        $kpis = null;
        if ($sucursal) {
            $abiertos = EstadoMantenimiento::where('es_abierto', true)->pluck('id');
            $equipos = Equipo::when($sucursalId, fn (Builder $q, $v) => $q->where('sucursal_id', $v));

            $kpis = [
                'total_equipos' => (clone $equipos)->count(),
                'valor_total' => (float) (clone $equipos)->sum('valor_adquisicion'),
                'operativos' => (clone $equipos)->whereHas('estado', fn (Builder $q) => $q->where('es_operativo', true))->count(),
                'fuera_operacion' => (clone $equipos)->whereHas('estado', fn (Builder $q) => $q->where('es_operativo', false))->count(),
                'mantenimientos_pendientes' => Mantenimiento::when($sucursalId, fn (Builder $q, $v) => $q->where('sucursal_id', $v))->whereIn('estado_id', $abiertos)->count(),
                'solicitudes_abiertas' => SolicitudMantenimiento::when($sucursalId, fn (Builder $q, $v) => $q->where('sucursal_id', $v))->whereIn('estado_id', $abiertos)->count(),
            ];
        }

        $orden = in_array($request->query('orden'), self::ORDENABLES, true) ? $request->query('orden') : 'codigo_activo';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';
        $texto = fn (string $clave): ?string => filled($request->query($clave)) ? trim((string) $request->query($clave)) : null;

        $listado = null;
        if ($sucursal) {
            $listado = Equipo::query()
                ->when($request->boolean('bajas'), fn (Builder $q) => $q->onlyTrashed())
                ->when($sucursalId, fn (Builder $q, $v) => $q->where('sucursal_id', $v))
                ->with(['tipo:id,nombre', 'marca:id,nombre', 'ubicacion:id,nombre', 'estado:id,nombre,color', 'bajaPor:id,nombre'])
                ->when($texto('codigo'), fn (Builder $q, $v) => $q->where('codigo_activo', 'like', "%{$v}%"))
                ->when($texto('descripcion'), fn (Builder $q, $v) => $q->where('descripcion', 'like', "%{$v}%"))
                ->when($texto('serie'), fn (Builder $q, $v) => $q->where('numero_serie', 'like', "%{$v}%"))
                ->when($texto('marca'), fn (Builder $q, $v) => $q->where(fn (Builder $s) => $s
                    ->where('modelo', 'like', "%{$v}%")
                    ->orWhereHas('marca', fn (Builder $b) => $b->where('nombre', 'like', "%{$v}%"))))
                ->when($request->integer('ubicacion_id'), fn (Builder $q, $v) => $q->where('ubicacion_id', $v))
                ->when($request->integer('tipo_id'), fn (Builder $q, $v) => $q->where('tipo_id', $v))
                ->when($request->integer('estado_id'), fn (Builder $q, $v) => $q->where('estado_id', $v))
                ->when($request->filled('valor'), fn (Builder $q) => $q->whereRaw(
                    'CAST(valor_adquisicion AS CHAR) LIKE ?',
                    ['%'.trim((string) $request->query('valor')).'%'],
                ))
                ->when($request->filled('registrado_por'), fn (Builder $q) => $q->whereIn(
                    'id',
                    Auditoria::idsCreadosPor(Equipo::class, trim((string) $request->query('registrado_por'))),
                ))
                ->orderBy($orden, $dir)
                ->paginate(self::POR_PAGINA)
                ->withQueryString();

            $creadores = Auditoria::creadoPorMasivo(Equipo::class, $listado->pluck('id'));
            $listado->through(fn (Equipo $e) => [
                'id' => $e->id,
                'codigo_activo' => $e->codigo_activo,
                'descripcion' => $e->descripcion,
                'tipo' => $e->tipo?->nombre,
                'marca' => $e->marca?->nombre,
                'modelo' => $e->modelo,
                'numero_serie' => $e->numero_serie,
                'ubicacion' => $e->ubicacion?->nombre,
                'estado' => $e->estado,
                'valor_adquisicion' => $e->valor_adquisicion,
                'proximo_mantenimiento' => $e->planes()->min('proxima_fecha'),
                'creado_por' => $creadores[$e->id]['usuario'] ?? null,
                'creado_en' => $creadores[$e->id]['fecha'] ?? null,
                'motivo_baja' => $e->motivo_baja,
                'baja_por' => $e->bajaPor?->nombre,
                'baja_en' => $e->baja_en,
            ]);
        }

        $catalogos = $this->catalogosFiltro();
        if ($sucursalId) {
            $catalogos['ubicaciones'] = collect($catalogos['ubicaciones'])->where('sucursal_id', $sucursalId)->values();
        }

        return Inertia::render('Inventario/Equipos/PorSucursal', [
            'resumen' => $resumen,
            'sucursalId' => $sucursalId,
            'kpis' => $kpis,
            'equipos' => $listado,
            'filtros' => $request->only([
                'codigo', 'descripcion', 'serie', 'marca', 'ubicacion_id', 'tipo_id', 'estado_id', 'valor', 'registrado_por', 'bajas',
            ]),
            'orden' => ['campo' => $orden, 'dir' => $dir],
            'catalogos' => $catalogos,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('equipos.crear');

        return Inertia::render('Inventario/Equipos/Form', [
            'equipo' => null,
            'codigoSugerido' => Folios::previsualizarCodigoEquipo(),
            'catalogos' => $this->catalogosForm(),
        ]);
    }

    public function store(GuardarEquipoRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except([
            'normas', 'motivo_cambio_ubicacion', 'foto_referencia',
            'plan_preventivo', 'plan_tipo_mantenimiento_id', 'plan_tipo_frecuencia', 'plan_valor_frecuencia',
        ]);

        $equipo = DB::transaction(function () use ($datos, $request) {
            $equipo = Equipo::create($datos);
            $equipo->normas()->sync($request->input('normas', []));

            if ($equipo->ubicacion_id) {
                $equipo->historialUbicacion()->create([
                    'ubicacion_origen_id' => null,
                    'ubicacion_destino_id' => $equipo->ubicacion_id,
                    'cambiado_por' => $request->user()->id,
                    'motivo' => 'Alta del equipo',
                    'cambiado_at' => now(),
                ]);
            }

            if ($request->hasFile('foto_referencia')) {
                $this->reemplazarFotoReferencia($equipo, $request);
            }

            // RF-de-alta: dejar programado el mantenimiento preventivo desde la creación (§5 obs. generales).
            if ($request->boolean('plan_preventivo')) {
                $fechaInicio = now();

                PlanMantenimiento::create([
                    'equipo_id' => $equipo->id,
                    'sucursal_id' => $equipo->sucursal_id,
                    'tipo_mantenimiento_id' => $request->input('plan_tipo_mantenimiento_id'),
                    'tipo_frecuencia' => $request->input('plan_tipo_frecuencia'),
                    'valor_frecuencia' => $request->input('plan_valor_frecuencia'),
                    'fecha_inicio' => $fechaInicio->toDateString(),
                    'proxima_fecha' => Frecuencia::siguiente($fechaInicio, $request->input('plan_tipo_frecuencia'), (int) $request->input('plan_valor_frecuencia'))->toDateString(),
                    'dias_aviso_anticipado' => 7,
                    'estado' => 'activo',
                ]);
            }

            return $equipo;
        });

        return redirect()->route('equipos.show', $equipo)->with('exito', 'Equipo registrado.');
    }

    /** Acepta también equipos dados de baja, para poder consultarlos (§13). */
    public function show(int $equipo): Response
    {
        $this->authorize('equipos.ver');

        $equipo = Equipo::withTrashed()->findOrFail($equipo);
        $equipo->load([
            'tipo:id,nombre', 'marca:id,nombre', 'sucursal:id,nombre', 'ubicacion:id,nombre',
            'proveedor:id,razon_social,nombre_comercial', 'responsable:id,nombre', 'estado',
            'normas:id,codigo,nombre',
            'documentos',
            'planes.tipo:id,nombre',
            'historialUbicacion.ubicacionOrigen:id,nombre',
            'historialUbicacion.ubicacionDestino:id,nombre',
            'historialUbicacion.cambiadoPor:id,nombre',
            'bajaPor:id,nombre',
        ]);

        return Inertia::render('Inventario/Equipos/Show', [
            'equipo' => $equipo,
            'sello' => $equipo->selloAuditoria(),
            'mantenimientos' => $equipo->mantenimientos()
                ->with(['tipo:id,nombre', 'estado:id,nombre', 'prioridad:id,nombre'])
                ->latest('id')
                ->limit(50)
                ->get(['id', 'folio', 'tipo_id', 'estado_id', 'prioridad_id', 'programado_inicio', 'completado_at']),
            'solicitudes' => $equipo->solicitudes()
                ->with('estado:id,nombre')
                ->latest('id')
                ->limit(20)
                ->get(['id', 'folio', 'estado_id', 'descripcion', 'solicitado_at']),
        ]);
    }

    public function edit(Equipo $equipo): Response
    {
        $this->authorize('equipos.editar');

        $equipo->load(['normas:id', 'documentos']);

        $equipoArr = $equipo->toArray();
        $equipoArr['foto_referencia'] = $equipo->documentos->firstWhere('pivot.rol', 'foto_referencia');

        return Inertia::render('Inventario/Equipos/Form', [
            'equipo' => $equipoArr,
            'catalogos' => $this->catalogosForm(),
        ]);
    }

    public function update(GuardarEquipoRequest $request, Equipo $equipo): RedirectResponse
    {
        $datos = $request->safe()->except([
            'normas', 'motivo_cambio_ubicacion', 'foto_referencia',
            'plan_preventivo', 'plan_tipo_mantenimiento_id', 'plan_tipo_frecuencia', 'plan_valor_frecuencia',
        ]);
        $ubicacionAnterior = $equipo->ubicacion_id;

        DB::transaction(function () use ($equipo, $datos, $request, $ubicacionAnterior): void {
            $equipo->update($datos);
            $equipo->normas()->sync($request->input('normas', []));

            // RF-035: registrar el cambio de ubicación conservando historial.
            if ($equipo->wasChanged('ubicacion_id')) {
                $equipo->historialUbicacion()->create([
                    'ubicacion_origen_id' => $ubicacionAnterior,
                    'ubicacion_destino_id' => $equipo->ubicacion_id,
                    'cambiado_por' => $request->user()->id,
                    'motivo' => $request->input('motivo_cambio_ubicacion') ?: 'Actualización del equipo',
                    'cambiado_at' => now(),
                ]);
            }

            if ($request->hasFile('foto_referencia')) {
                $this->reemplazarFotoReferencia($equipo, $request);
            }
        });

        return redirect()->route('equipos.show', $equipo)->with('exito', 'Equipo actualizado.');
    }

    public function destroy(Request $request, Equipo $equipo): RedirectResponse
    {
        $this->authorize('equipos.desactivar');

        $datos = $request->validate([
            'motivo' => ['required', 'string', 'max:2000'],
            'evidencia' => ['nullable', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        $sucursalId = $equipo->sucursal_id;

        if ($request->hasFile('evidencia')) {
            $archivo = $request->file('evidencia');
            $ruta = $archivo->store('documentos/'.now()->format('Y/m'), 'local');

            $documento = Documento::create([
                'disco' => 'local',
                'ruta' => $ruta,
                'nombre_original' => $archivo->getClientOriginalName(),
                'titulo' => 'Evidencia de baja',
                'categoria' => 'baja',
                'tipo_mime' => $archivo->getClientMimeType(),
                'tamano' => $archivo->getSize(),
                'checksum' => hash_file('sha256', $archivo->getRealPath()),
                'visibilidad' => 'privado',
                'subido_por' => $request->user()->id,
            ]);

            $equipo->documentos()->attach($documento->id, ['rol' => 'baja']);
        }

        $equipo->update([
            'motivo_baja' => $datos['motivo'],
            'baja_por' => $request->user()->id,
            'baja_en' => now(),
        ]);
        $equipo->delete();

        return redirect()->route('equipos.por_sucursal', ['sucursal_id' => $sucursalId])->with('exito', 'Equipo dado de baja.');
    }

    /** Reactiva un equipo dado de baja — RF de trazabilidad (§13). */
    public function restore(int $equipo): RedirectResponse
    {
        $this->authorize('equipos.editar');

        $registro = Equipo::onlyTrashed()->findOrFail($equipo);
        $registro->restore();
        $registro->update(['motivo_baja' => null, 'baja_por' => null, 'baja_en' => null]);

        return back()->with('exito', 'Equipo reactivado.');
    }

    /** Datos para imprimir / mostrar el código QR del activo (Propuesta SIGAM §6.1). */
    public function qr(Equipo $equipo): Response
    {
        $this->authorize('equipos.ver');

        $url = route('equipos.escanear', $equipo->token_qr);

        return Inertia::render('Inventario/Equipos/Qr', [
            'equipo' => $equipo->only(['id', 'codigo_activo', 'descripcion', 'token_qr']),
            'url' => $url,
            'svg' => $this->qrSvg($url),
        ]);
    }

    /** QR del activo en JSON, para mostrarlo en un modal sin recargar. */
    public function qrData(Equipo $equipo): JsonResponse
    {
        $this->authorize('equipos.ver');

        $url = route('equipos.escanear', $equipo->token_qr);

        return response()->json([
            'codigo_activo' => $equipo->codigo_activo,
            'descripcion' => $equipo->descripcion,
            'url' => $url,
            'png' => 'data:image/png;base64,'.base64_encode($this->qrPng($url)),
        ]);
    }

    /** Etiqueta imprimible del código QR del activo en PDF (RF-036). */
    public function qrPdf(Equipo $equipo): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('equipos.ver');

        $url = route('equipos.escanear', $equipo->token_qr);

        $pdf = Pdf::loadView('pdf.qr-equipo', [
            'equipo' => $equipo,
            'url' => $url,
            'qr' => 'data:image/png;base64,'.base64_encode($this->qrPng($url)),
            'logo' => \App\Support\Marca::logoDataUri(),
        ])->setPaper('a6');

        return $pdf->download("qr-{$equipo->codigo_activo}.pdf");
    }

    /** Imagen JPG del código QR del activo, para descargar. */
    public function qrImagen(Equipo $equipo): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('equipos.ver');

        $url = route('equipos.escanear', $equipo->token_qr);

        $png = imagecreatefromstring($this->qrPng($url));
        ob_start();
        imagejpeg($png, null, 92);
        $jpg = (string) ob_get_clean();
        imagedestroy($png);

        return ResponseFactory::make($jpg, 200, [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => "attachment; filename=\"qr-{$equipo->codigo_activo}.jpg\"",
        ]);
    }

    /** Consulta del expediente por escaneo de QR. Requiere sesión autenticada. */
    public function escanear(string $token): RedirectResponse
    {
        $this->authorize('equipos.ver');

        $equipo = Equipo::where('token_qr', $token)->firstOrFail();

        return redirect()->route('equipos.show', $equipo);
    }

    /** Plantilla Excel (.xlsx) para la carga masiva de equipos. */
    public function plantillaImportar(): BinaryFileResponse
    {
        $this->authorize('equipos.crear');

        $ruta = tempnam(sys_get_temp_dir(), 'plantilla').'.xlsx';

        $writer = new XlsxWriter;
        $writer->openToFile($ruta);
        $writer->addRow(XlsxRow::fromValues(ImportadorEquipos::COLUMNAS));
        $writer->addRow(XlsxRow::fromValues([
            'EQ-EJEMPLO-01', 'Compresor de aire 5HP', '7501234567890', 'Climatización', 'Carrier',
            'CR-5HP', 'SN-000123', 'Sucursal Matriz', 'Área de máquinas', 'Operativo',
            'Servicios Técnicos SA', 'responsable@correo.com', '2024-03-15', 'F-4821', '45000',
            '2026-03-15', '10 años', 'Equipo instalado en planta baja',
        ]));
        $writer->close();

        return response()->download($ruta, 'plantilla-equipos.xlsx')->deleteFileAfterSend();
    }

    /** Carga masiva de equipos desde un archivo Excel. */
    public function importar(Request $request, ImportadorEquipos $importador): RedirectResponse
    {
        $this->authorize('equipos.crear');

        $request->validate([
            'archivo' => ['required', 'file', 'max:5120', 'mimes:xlsx,xls'],
        ], [
            'archivo.mimes' => 'El archivo debe ser un Excel (.xlsx o .xls).',
        ], ['archivo' => 'archivo']);

        $resultado = $importador->procesar($request->file('archivo'), $request->user()->id);

        $mensaje = "{$resultado['creados']} equipos importados";
        if ($resultado['errores'] !== []) {
            $mensaje .= ', '.count($resultado['errores']).' fila(s) con errores';
        }

        return back()->with(
            $resultado['creados'] > 0 ? 'exito' : 'error',
            $mensaje.'.',
        )->with('importacion', $resultado);
    }

    /** Sube la nueva foto de referencia y reemplaza la anterior, si había una. */
    private function reemplazarFotoReferencia(Equipo $equipo, Request $request): void
    {
        $anterior = $equipo->documentos()->wherePivot('rol', 'foto_referencia')->first();
        if ($anterior) {
            $equipo->documentos()->detach($anterior->id);
            $anterior->delete();
        }

        $archivo = $request->file('foto_referencia');
        $ruta = $archivo->store('documentos/'.now()->format('Y/m'), 'local');

        $documento = Documento::create([
            'disco' => 'local',
            'ruta' => $ruta,
            'nombre_original' => $archivo->getClientOriginalName(),
            'titulo' => 'Foto de referencia',
            'categoria' => 'foto_referencia',
            'tipo_mime' => $archivo->getClientMimeType(),
            'tamano' => $archivo->getSize(),
            'checksum' => hash_file('sha256', $archivo->getRealPath()),
            'visibilidad' => 'privado',
            'subido_por' => $request->user()->id,
        ]);

        $equipo->documentos()->attach($documento->id, ['rol' => 'foto_referencia']);
    }

    /** @return array<string, mixed> */
    private function catalogosFiltro(): array
    {
        return [
            'sucursales' => Sucursal::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'tipos' => TipoEquipo::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'estados' => EstadoEquipo::activos()->orderBy('nombre')->get(['id', 'nombre', 'color']),
            'proveedores' => Proveedor::activos()->orderBy('razon_social')->get(['id', 'razon_social']),
            'ubicaciones' => Ubicacion::activos()->orderBy('nombre')->get(['id', 'nombre', 'sucursal_id']),
        ];
    }

    /** @return array<string, mixed> */
    private function catalogosForm(): array
    {
        return [
            ...$this->catalogosFiltro(),
            'marcas' => Marca::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'responsables' => Usuario::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre', 'apellidos']),
            'normas' => Norma::activos()->orderBy('codigo')->get(['id', 'codigo', 'nombre']),
            'tiposMantenimiento' => TipoMantenimiento::activos()->where('categoria', 'preventivo')->orderBy('nombre')->get(['id', 'nombre']),
        ];
    }

    /** Genera el QR del token del activo como SVG embebible. */
    private function qrSvg(string $contenido): string
    {
        $writer = new Writer(new ImageRenderer(
            new RendererStyle(220, 1),
            new SvgImageBackEnd,
        ));

        return $writer->writeString($contenido);
    }

    /** Genera el QR del token del activo como PNG (bytes crudos). */
    private function qrPng(string $contenido): string
    {
        return (new QrBuilder(
            writer: new PngWriter,
            data: $contenido,
            size: 320,
            margin: 12,
        ))->build()->getString();
    }
}
