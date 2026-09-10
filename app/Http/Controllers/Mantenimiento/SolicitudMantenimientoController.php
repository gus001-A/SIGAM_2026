<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mantenimiento\GuardarSolicitudRequest;
use App\Models\Equipo;
use App\Models\EstadoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\Prioridad;
use App\Models\SolicitudMantenimiento;
use App\Models\Sucursal;
use App\Models\TipoMantenimiento;
use App\Support\CicloMantenimiento;
use App\Support\Folios;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Solicitudes de mantenimiento correctivo. Especificación v2.0 §5.10 / RF-043.
 */
class SolicitudMantenimientoController extends Controller
{
    private const ORDENABLES = ['folio', 'solicitado_at', 'fecha_requerida', 'id'];

    private const POR_PAGINA = 15;

    public function index(Request $request): Response
    {
        $this->authorize('solicitudes.ver');

        $usuario = $request->user();
        $orden = in_array($request->query('orden'), self::ORDENABLES, true) ? $request->query('orden') : 'id';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';
        $texto = fn (string $c): ?string => filled($request->query($c)) ? trim((string) $request->query($c)) : null;

        $solicitudes = SolicitudMantenimiento::query()
            ->with([
                'equipo:id,codigo_activo,descripcion',
                'sucursal:id,nombre',
                'solicitante:id,nombre',
                'prioridad:id,nombre,color',
                'estado:id,nombre,clave',
            ])
            // El usuario básico solo ve sus propias solicitudes (§4).
            ->when(
                ! $usuario->hasAnyRole(['superadministrador', 'supervisor', 'auditor']),
                fn (Builder $q) => $q->where('solicitado_por', $usuario->id),
            )
            ->when($texto('folio'), fn (Builder $q, $v) => $q->where('folio', 'like', "%{$v}%"))
            ->when($texto('equipo'), fn (Builder $q, $v) => $q->whereHas('equipo', fn (Builder $e) => $e
                ->where('codigo_activo', 'like', "%{$v}%")->orWhere('descripcion', 'like', "%{$v}%")))
            ->when($texto('solicitante'), fn (Builder $q, $v) => $q->whereHas('solicitante', fn (Builder $u) => $u->where('nombre', 'like', "%{$v}%")))
            ->when($request->integer('sucursal_id'), fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->when($request->integer('prioridad_id'), fn (Builder $q, $v) => $q->where('prioridad_id', $v))
            ->when($request->integer('estado_id'), fn (Builder $q, $v) => $q->where('estado_id', $v))
            ->orderBy($orden, $dir)
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (SolicitudMantenimiento $s) => [
                'id' => $s->id,
                'folio' => $s->folio,
                'equipo' => $s->equipo ? "{$s->equipo->codigo_activo} · {$s->equipo->descripcion}" : null,
                'sucursal' => $s->sucursal?->nombre,
                'prioridad' => $s->prioridad,
                'estado' => $s->estado,
                'solicitante' => $s->solicitante?->nombre,
                'solicitado_at' => $s->solicitado_at,
                'fecha_requerida' => $s->fecha_requerida,
            ]);

        return Inertia::render('Mantenimiento/Solicitudes/Index', [
            'solicitudes' => $solicitudes,
            'filtros' => $request->only(['folio', 'equipo', 'solicitante', 'sucursal_id', 'prioridad_id', 'estado_id']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
            'catalogos' => [
                'sucursales' => Sucursal::orderBy('nombre')->get(['id', 'nombre']),
                'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre', 'color']),
                'estados' => EstadoMantenimiento::activos()->orderBy('orden')->get(['id', 'nombre', 'clave']),
                'equipos' => $request->user()->can('solicitudes.crear')
                    ? Equipo::orderBy('codigo_activo')->get(['id', 'codigo_activo', 'descripcion'])
                    : [],
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('solicitudes.crear');

        return Inertia::render('Mantenimiento/Solicitudes/Form', [
            'equipos' => Equipo::orderBy('codigo_activo')->get(['id', 'codigo_activo', 'descripcion', 'sucursal_id']),
            'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre']),
            'preseleccion' => ['equipo_id' => $request->integer('equipo_id') ?: null],
        ]);
    }

    public function store(GuardarSolicitudRequest $request): RedirectResponse
    {
        $equipo = Equipo::findOrFail($request->integer('equipo_id'));

        $solicitud = SolicitudMantenimiento::create([
            ...$request->validated(),
            'folio' => Folios::solicitud(),
            'sucursal_id' => $equipo->sucursal_id,
            'solicitado_por' => $request->user()->id,
            'estado_id' => CicloMantenimiento::estado('solicitado')->id,
            'solicitado_at' => now(),
        ]);

        return redirect()->route('solicitudes.show', $solicitud)->with('exito', "Solicitud {$solicitud->folio} registrada.");
    }

    public function show(SolicitudMantenimiento $solicitud): Response
    {
        $this->authorize('solicitudes.ver');
        $this->verificarPertenencia($solicitud);

        $solicitud->load([
            'equipo:id,codigo_activo,descripcion,sucursal_id',
            'sucursal:id,nombre',
            'solicitante:id,nombre',
            'revisadoPor:id,nombre',
            'prioridad:id,nombre,color',
            'estado:id,nombre,clave',
            'mantenimientos:id,folio,solicitud_id,estado_id',
            'mantenimientos.estado:id,nombre',
            'documentos',
        ]);

        return Inertia::render('Mantenimiento/Solicitudes/Show', [
            'solicitud' => $solicitud,
            'puedeConvertir' => $solicitud->mantenimientos->isEmpty()
                && $solicitud->estado->clave !== 'cancelado'
                && request()->user()->can('mantenimientos.crear'),
            'catalogos' => [
                'tipos' => TipoMantenimiento::activos()->orderBy('nombre')->get(['id', 'nombre', 'categoria']),
                'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre']),
            ],
        ]);
    }

    public function update(GuardarSolicitudRequest $request, SolicitudMantenimiento $solicitud): RedirectResponse
    {
        $this->verificarPertenencia($solicitud);

        if ($solicitud->mantenimientos()->exists()) {
            return back()->with('error', 'La solicitud ya fue convertida en orden y no puede editarse.');
        }

        $solicitud->update($request->validated());

        return back()->with('exito', 'Solicitud actualizada.');
    }

    /** Convierte la solicitud en una orden de mantenimiento. RF-043. */
    public function autorizar(Request $request, SolicitudMantenimiento $solicitud): RedirectResponse
    {
        $this->authorize('mantenimientos.crear');

        $datos = $request->validate([
            'tipo_id' => ['required', 'integer', Rule::exists('tipos_mantenimiento', 'id')],
            'prioridad_id' => ['required', 'integer', Rule::exists('prioridades', 'id')],
            'programado_inicio' => ['nullable', 'date'],
            'programado_fin' => ['nullable', 'date', 'after_or_equal:programado_inicio'],
        ]);

        if ($solicitud->mantenimientos()->exists()) {
            return back()->with('error', 'La solicitud ya tiene una orden asociada.');
        }

        $mantenimiento = DB::transaction(function () use ($solicitud, $datos, $request) {
            $autorizado = CicloMantenimiento::estado('autorizado');

            $mantenimiento = Mantenimiento::create([
                'folio' => Folios::mantenimiento(),
                'solicitud_id' => $solicitud->id,
                'equipo_id' => $solicitud->equipo_id,
                'sucursal_id' => $solicitud->sucursal_id,
                'tipo_id' => $datos['tipo_id'],
                'prioridad_id' => $datos['prioridad_id'],
                'estado_id' => $autorizado->id,
                'programado_inicio' => $datos['programado_inicio'] ?? null,
                'programado_fin' => $datos['programado_fin'] ?? null,
                'problema_reportado' => $solicitud->descripcion,
                'creado_por' => $request->user()->id,
                'autorizado_por' => $request->user()->id,
                'autorizado_at' => now(),
            ]);

            $mantenimiento->historialEstados()->create([
                'estado_origen_id' => null,
                'estado_destino_id' => $autorizado->id,
                'cambiado_por' => $request->user()->id,
                'nota' => "Generada desde la solicitud {$solicitud->folio}",
                'cambiado_at' => now(),
            ]);

            $solicitud->update([
                'estado_id' => $autorizado->id,
                'revisado_por' => $request->user()->id,
                'revisado_at' => now(),
            ]);

            return $mantenimiento;
        });

        return redirect()->route('mantenimientos.show', $mantenimiento)
            ->with('exito', "Orden {$mantenimiento->folio} creada desde la solicitud.");
    }

    public function rechazar(Request $request, SolicitudMantenimiento $solicitud): RedirectResponse
    {
        $this->authorize('solicitudes.editar');

        $datos = $request->validate(['motivo_rechazo' => ['required', 'string', 'max:500']]);

        $solicitud->update([
            'estado_id' => CicloMantenimiento::estado('cancelado')->id,
            'revisado_por' => $request->user()->id,
            'revisado_at' => now(),
            'motivo_rechazo' => $datos['motivo_rechazo'],
        ]);

        return back()->with('exito', 'Solicitud rechazada.');
    }

    /** El usuario básico solo puede acceder a sus propias solicitudes (§4). */
    private function verificarPertenencia(SolicitudMantenimiento $solicitud): void
    {
        $usuario = request()->user();

        abort_unless(
            $usuario->hasAnyRole(['superadministrador', 'supervisor', 'auditor', 'tecnico'])
                || $solicitud->solicitado_por === $usuario->id,
            403,
        );
    }
}
