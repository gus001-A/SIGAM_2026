<?php

namespace App\Http\Controllers\Tareas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tareas\GuardarTareaRequest;
use App\Models\Prioridad;
use App\Models\Tarea;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\CicloTarea;
use App\Support\Notificaciones;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Seguimiento de tareas. Propuesta técnica SIGAM — anexo "TAREAS".
 */
class TareaController extends Controller
{
    private const ORDENABLES = ['fecha_limite', 'created_at', 'id'];

    private const POR_PAGINA = 15;

    private const ESTADOS_ACTIVOS = ['pendiente', 'en_proceso'];

    private const ESTADOS_COMPLETADOS = ['realizada', 'cancelada'];

    public function index(Request $request): Response
    {
        $this->authorize('tareas.ver');

        $usuario = $request->user();
        $orden = in_array($request->query('orden'), self::ORDENABLES, true) ? $request->query('orden') : 'fecha_limite';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';
        $texto = fn (string $c): ?string => filled($request->query($c)) ? trim((string) $request->query($c)) : null;
        $vista = $request->query('vista') === 'completadas' ? 'completadas' : 'activas';

        $tareas = Tarea::query()
            ->with(['responsables' => fn ($q) => $q->wherePivotNull('desasignado_at'), 'prioridad:id,nombre,color', 'creadoPor:id,nombre'])
            // Solo el superadministrador ve las tareas de todos; el resto solo
            // ve las que le asignaron o las que él mismo registró.
            ->when(
                ! $usuario->hasRole('superadministrador'),
                fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereHas('responsables', fn (Builder $r) => $r
                        ->where('usuarios.id', $usuario->id)->whereNull('tarea_responsables.desasignado_at'))
                    ->orWhere('creado_por', $usuario->id)),
            )
            ->when($vista === 'completadas', fn (Builder $q) => $q->whereIn('estado', self::ESTADOS_COMPLETADOS))
            ->when($vista === 'activas', fn (Builder $q) => $q->whereIn('estado', self::ESTADOS_ACTIVOS))
            ->when($texto('descripcion'), fn (Builder $q, $v) => $q->where(fn (Builder $w) => $w
                ->where('titulo', 'like', "%{$v}%")->orWhere('descripcion', 'like', "%{$v}%")))
            ->when($vista === 'activas' && $texto('estado'), fn (Builder $q, $v) => $q->where('estado', $v))
            ->when($request->integer('prioridad_id'), fn (Builder $q, $v) => $q->where('prioridad_id', $v))
            ->when($texto('responsable'), fn (Builder $q, $v) => $q->whereHas(
                'responsables',
                fn (Builder $r) => $r->whereNull('tarea_responsables.desasignado_at')
                    ->where(fn (Builder $u) => $u->where('nombre', 'like', "%{$v}%")->orWhere('apellidos', 'like', "%{$v}%")),
            ))
            ->when($request->filled('desde'), fn (Builder $q) => $q->whereDate('fecha_limite', '>=', $request->query('desde')))
            ->when($request->filled('hasta'), fn (Builder $q) => $q->whereDate('fecha_limite', '<=', $request->query('hasta')))
            ->when($vista === 'activas' && $request->boolean('vencidas'), fn (Builder $q) => $q
                ->whereDate('fecha_limite', '<', today()))
            ->when($texto('registrado_por'), fn (Builder $q, $v) => $q->whereIn(
                'id',
                Auditoria::idsCreadosPor(Tarea::class, $v),
            ))
            ->orderBy($orden, $dir)
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        $creadores = Auditoria::creadoPorMasivo(Tarea::class, $tareas->pluck('id'));
        $tareas->through(fn (Tarea $t) => [
            'id' => $t->id,
            'titulo' => $t->titulo,
            'descripcion' => $t->descripcion,
            'estado' => $t->estado,
            'fecha_limite' => $t->fecha_limite,
            'vencida' => in_array($t->estado, self::ESTADOS_ACTIVOS, true) && $t->fecha_limite->isPast(),
            'prioridad' => $t->prioridad,
            'responsables' => $t->responsables->pluck('nombre_completo')->implode(', '),
            'creado_por' => $creadores[$t->id]['usuario'] ?? null,
            'creado_en' => $creadores[$t->id]['fecha'] ?? null,
        ]);

        return Inertia::render('Tareas/Index', [
            'tareas' => $tareas,
            'vista' => $vista,
            'filtros' => $request->only(['descripcion', 'estado', 'responsable', 'prioridad_id', 'desde', 'hasta', 'vencidas', 'registrado_por']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
            'catalogos' => [
                'usuarios' => Usuario::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre', 'apellidos']),
                'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre', 'color']),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('tareas.crear');

        return Inertia::render('Tareas/Form', [
            'usuarios' => Usuario::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre', 'apellidos']),
            'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre']),
        ]);
    }

    public function store(GuardarTareaRequest $request): RedirectResponse
    {
        $tarea = DB::transaction(function () use ($request) {
            $tarea = Tarea::create([
                ...$request->safe()->only(['titulo', 'descripcion', 'fecha_limite', 'prioridad_id']),
                'estado' => 'pendiente',
                'creado_por' => $request->user()->id,
            ]);

            $responsables = $request->validated('responsables');
            $tarea->responsables()->attach($responsables, [
                'asignado_por' => $request->user()->id,
                'asignado_at' => now(),
            ]);

            $tarea->historialEstados()->create([
                'estado_origen' => null,
                'estado_destino' => 'pendiente',
                'cambiado_por' => $request->user()->id,
                'nota' => 'Tarea creada',
                'cambiado_at' => now(),
            ]);

            foreach ($responsables as $usuarioId) {
                if ($usuarioId === $request->user()->id) {
                    continue;
                }
                Notificaciones::crear(
                    $usuarioId,
                    'tarea_asignada',
                    "Nueva tarea: {$tarea->titulo}",
                    'Se te asignó una tarea con fecha límite '.$tarea->fecha_limite->format('d/m/Y').'.',
                    ['ref' => "tarea:{$tarea->id}", 'url' => route('tareas.show', $tarea->id)],
                );
            }

            return $tarea;
        });

        return redirect()->route('tareas.show', $tarea)->with('exito', 'Tarea registrada.');
    }

    public function show(Tarea $tarea): Response
    {
        $this->authorize('tareas.ver');
        $this->verificarPertenencia($tarea);

        $tarea->load([
            'asignaciones.usuario:id,nombre,apellidos',
            'asignaciones.asignadoPor:id,nombre',
            'historialEstados.cambiadoPor:id,nombre,apellidos',
            'prioridad:id,nombre,color',
            'creadoPor:id,nombre',
            'documentos',
        ]);

        return Inertia::render('Tareas/Show', [
            'tarea' => $tarea,
            'sello' => $tarea->selloAuditoria(),
            'transicionesPosibles' => CicloTarea::siguientes($tarea->estado),
            'catalogos' => [
                'usuarios' => Usuario::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre', 'apellidos']),
                'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre']),
            ],
        ]);
    }

    public function update(GuardarTareaRequest $request, Tarea $tarea): RedirectResponse
    {
        $this->verificarPertenencia($tarea);

        if (CicloTarea::esFinal($tarea->estado)) {
            return back()->with('error', 'No se puede editar una tarea realizada o cancelada.');
        }

        $tarea->update($request->safe()->only(['titulo', 'descripcion', 'fecha_limite', 'prioridad_id']));

        $this->notificarResponsables($tarea, $request->user()->id, 'tarea_modificada', "Tarea modificada: {$tarea->titulo}", 'Se actualizaron los datos de una tarea que tienes asignada.');

        return back()->with('exito', 'Tarea actualizada.');
    }

    /** Baja lógica: solo se permite una vez que la tarea llegó a un estado final. */
    public function destroy(Tarea $tarea): RedirectResponse
    {
        $this->authorize('tareas.desactivar');

        if (! CicloTarea::esFinal($tarea->estado)) {
            return back()->with('error', 'Solo se pueden eliminar tareas realizadas o canceladas.');
        }

        $tarea->delete();

        return redirect()->route('tareas.index')->with('exito', 'Tarea eliminada.');
    }

    /** Transición de estado validada contra la máquina de estados. */
    public function transicion(Request $request, Tarea $tarea): RedirectResponse
    {
        $this->authorize('tareas.editar');
        $this->verificarPertenencia($tarea);

        $destino = (string) $request->string('estado');
        $origen = $tarea->estado;

        if (! CicloTarea::permite($origen, $destino)) {
            return back()->with('error', "Transición no permitida: {$origen} → {$destino}.");
        }

        $reglas = ['nota' => ['nullable', 'string', 'max:1000']];
        if ($destino === 'realizada') {
            $this->authorize('tareas.cerrar');
            $reglas['nota'] = ['required', 'string', 'max:1000'];
            $reglas['costo'] = ['nullable', 'numeric', 'min:0'];
        }
        if ($destino === 'cancelada') {
            $reglas['nota'] = ['required', 'string', 'max:1000'];
        }
        $datos = $request->validate($reglas);

        DB::transaction(function () use ($tarea, $origen, $destino, $datos, $request): void {
            $tarea->estado = $destino;
            match ($destino) {
                'en_proceso' => $tarea->fill(['iniciada_at' => now(), 'nota_avance' => $datos['nota'] ?? null]),
                'realizada' => $tarea->fill(['realizada_at' => now(), 'nota_cierre' => $datos['nota'], 'costo' => $datos['costo'] ?? null]),
                'cancelada' => $tarea->fill(['cancelada_at' => now(), 'nota_cancelacion' => $datos['nota']]),
                default => null,
            };
            $tarea->save();

            $tarea->historialEstados()->create([
                'estado_origen' => $origen,
                'estado_destino' => $destino,
                'cambiado_por' => $request->user()->id,
                'nota' => $datos['nota'] ?? null,
                'cambiado_at' => now(),
            ]);
        });

        $this->notificarResponsables(
            $tarea,
            $request->user()->id,
            'tarea_modificada',
            "Tarea actualizada: {$tarea->titulo}",
            'Cambió a «'.CicloTarea::etiqueta($destino).'».',
        );

        return back()->with('exito', 'Tarea movida a «'.CicloTarea::etiqueta($destino).'».');
    }

    /** Notifica a los responsables activos de la tarea, menos a quien hizo el cambio. */
    private function notificarResponsables(Tarea $tarea, int $exceptoUsuarioId, string $tipo, string $titulo, string $cuerpo): void
    {
        $datos = ['ref' => "{$tipo}:{$tarea->id}:".now()->timestamp, 'url' => route('tareas.show', $tarea->id)];

        $tarea->responsables()
            ->wherePivotNull('desasignado_at')
            ->where('usuarios.id', '!=', $exceptoUsuarioId)
            ->pluck('usuarios.id')
            ->each(fn ($usuarioId) => Notificaciones::crear($usuarioId, $tipo, $titulo, $cuerpo, $datos));
    }

    /** El técnico y el usuario básico solo acceden a tareas que tienen asignadas o que ellos registraron. */
    private function verificarPertenencia(Tarea $tarea): void
    {
        $usuario = request()->user();

        abort_unless(
            $usuario->hasRole('superadministrador')
                || $tarea->creado_por === $usuario->id
                || $tarea->responsables()->wherePivotNull('desasignado_at')->where('usuarios.id', $usuario->id)->exists(),
            403,
        );
    }
}
