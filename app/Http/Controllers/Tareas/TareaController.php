<?php

namespace App\Http\Controllers\Tareas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tareas\GuardarTareaRequest;
use App\Models\CategoriaTarea;
use App\Models\HistorialEstadoTarea;
use App\Models\Material;
use App\Models\Prioridad;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\Usuario;
use App\Support\Auditoria;
use App\Support\CicloTarea;
use App\Support\Evidencias;
use App\Support\Notificaciones;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

        // Alcance visible del usuario (sin filtros de la vista/búsqueda) para
        // los KPIs — así se quedan estables al cambiar de pestaña o filtrar.
        $visibles = fn (Builder $q) => $q->when(
            ! $usuario->hasRole('superadministrador'),
            fn (Builder $w) => $w->where(fn (Builder $ww) => $ww
                ->whereHas('responsables', fn (Builder $r) => $r
                    ->where('usuarios.id', $usuario->id)->whereNull('tarea_responsables.desasignado_at'))
                ->orWhere('creado_por', $usuario->id)),
        );

        $kpis = [
            'total' => Tarea::query()->tap($visibles)->count(),
            'vencidas' => Tarea::query()->tap($visibles)
                ->whereIn('estado', self::ESTADOS_ACTIVOS)->whereDate('fecha_limite', '<', today())->count(),
            'pendientes' => Tarea::query()->tap($visibles)->where('estado', 'pendiente')->count(),
            'en_proceso' => Tarea::query()->tap($visibles)->where('estado', 'en_proceso')->count(),
            'realizadas' => Tarea::query()->tap($visibles)->where('estado', 'realizada')->count(),
            'canceladas' => Tarea::query()->tap($visibles)->where('estado', 'cancelada')->count(),
        ];

        $tareas = Tarea::query()
            ->with([
                'responsables' => fn ($q) => $q->wherePivotNull('desasignado_at'),
                'prioridad:id,nombre,color', 'creadoPor:id,nombre',
                'proyecto:id,nombre', 'categoriaTarea:id,nombre',
            ])
            // Solo el superadministrador ve las tareas de todos; el resto solo
            // ve las que le asignaron o las que él mismo registró.
            ->tap($visibles)
            ->when($vista === 'completadas', fn (Builder $q) => $q->whereIn('estado', self::ESTADOS_COMPLETADOS))
            ->when($vista === 'activas', fn (Builder $q) => $q->whereIn('estado', self::ESTADOS_ACTIVOS))
            ->when($texto('descripcion'), fn (Builder $q, $v) => $q->where(fn (Builder $w) => $w
                ->where('titulo', 'like', "%{$v}%")->orWhere('descripcion', 'like', "%{$v}%")))
            ->when($vista === 'activas' && $texto('estado'), fn (Builder $q, $v) => $q->where('estado', $v))
            ->when($request->integer('prioridad_id'), fn (Builder $q, $v) => $q->where('prioridad_id', $v))
            ->when($texto('clasificacion'), fn (Builder $q, $v) => $q->where('clasificacion', $v))
            ->when($request->integer('proyecto_id'), fn (Builder $q, $v) => $q->where('proyecto_id', $v))
            ->when($request->integer('categoria_tarea_id'), fn (Builder $q, $v) => $q->where('categoria_tarea_id', $v))
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
            'retraso_dias' => $t->diasRetraso(),
            'prioridad' => $t->prioridad,
            'clasificacion' => $t->clasificacion,
            'proyecto' => $t->proyecto,
            'categoria_tarea' => $t->categoriaTarea,
            'responsables' => $t->responsables->pluck('nombre_completo')->implode(', '),
            'creado_por' => $creadores[$t->id]['usuario'] ?? null,
            'creado_en' => $creadores[$t->id]['fecha'] ?? null,
        ]);

        return Inertia::render('Tareas/Index', [
            'tareas' => $tareas,
            'kpis' => $kpis,
            'vista' => $vista,
            'filtros' => $request->only(['descripcion', 'estado', 'responsable', 'prioridad_id', 'clasificacion', 'proyecto_id', 'categoria_tarea_id', 'desde', 'hasta', 'vencidas', 'registrado_por']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
            'catalogos' => [
                'usuarios' => Usuario::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre', 'apellidos']),
                'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre', 'color']),
                'proyectos' => Proyecto::activos()->orderBy('nombre')->get(['id', 'nombre']),
                'categorias' => CategoriaTarea::activos()->orderBy('nombre')->get(['id', 'nombre']),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('tareas.crear');

        return Inertia::render('Tareas/Form', [
            'usuarios' => Usuario::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre', 'apellidos']),
            'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre']),
            'proyectos' => Proyecto::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'categorias' => CategoriaTarea::activos()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(GuardarTareaRequest $request): RedirectResponse
    {
        $tarea = DB::transaction(function () use ($request) {
            $tarea = Tarea::create([
                ...$request->datosClasificados(),
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
                $titulo = "Nueva tarea: {$tarea->titulo}";
                $cuerpo = 'Se te asignó una tarea con fecha límite '.$tarea->fecha_limite->format('d/m/Y').'.';
                $url = route('tareas.show', $tarea->id);

                Notificaciones::crear($usuarioId, 'tarea_asignada', $titulo, $cuerpo, ['ref' => "tarea:{$tarea->id}", 'url' => $url]);
                Notificaciones::correo($usuarioId, $titulo, $cuerpo, $url);
            }

            return $tarea;
        });

        return redirect()->route('tareas.show', $tarea)->with('exito', 'Tarea registrada.');
    }

    public function show(Request $request, Tarea $tarea): Response
    {
        $this->authorize('tareas.ver');
        $this->verificarPertenencia($tarea);

        $tarea->load([
            'asignaciones.usuario:id,nombre,apellidos,telefono',
            'asignaciones.asignadoPor:id,nombre',
            'historialEstados.cambiadoPor:id,nombre,apellidos',
            'historialEstados.evidencias',
            'prioridad:id,nombre,color',
            'proyecto:id,nombre',
            'categoriaTarea:id,nombre',
            'creadoPor:id,nombre',
            'materiales.material:id,nombre',
        ]);

        // El documento expone su URL de previsualización segura (no es una
        // relación, así que hay que llamar al método explícitamente).
        $tarea->historialEstados->each(fn (HistorialEstadoTarea $hh) => $hh->evidencias
            ->each(fn ($doc) => $doc->setAttribute('url', $doc->url())));

        return Inertia::render('Tareas/Show', [
            'tarea' => $tarea,
            'retrasoDias' => $tarea->diasRetraso(),
            'bitacora' => $tarea->bitacoraCambios(30, $tarea->historialEstados
                ->map(fn (HistorialEstadoTarea $h) => $this->movimientoBitacora($h))
                ->all()),
            'sello' => $tarea->selloAuditoria(),
            'transicionesPosibles' => CicloTarea::siguientes($tarea->estado),
            'avances' => $this->resumenAvances($tarea),
            'pendientesAvance' => $tarea->participantesPendientes()->pluck('nombre_completo')->values(),
            'soyParticipante' => $tarea->asignaciones()->whereNull('desasignado_at')->where('usuario_id', $request->user()->id)->exists(),
            'soyPrincipal' => $this->esPrincipal($tarea, $request->user()->id),
            'catalogos' => [
                'usuarios' => Usuario::where('estado', 'activo')->orderBy('nombre')->get(['id', 'nombre', 'apellidos']),
                'prioridades' => Prioridad::activos()->orderBy('nivel')->get(['id', 'nombre']),
                'proyectos' => Proyecto::activos()->orderBy('nombre')->get(['id', 'nombre']),
                'categorias' => CategoriaTarea::activos()->orderBy('nombre')->get(['id', 'nombre']),
                'materiales' => Material::activos()->orderBy('nombre')->get(['id', 'nombre', 'unidad', 'costo_referencia']),
            ],
        ]);
    }

    public function update(GuardarTareaRequest $request, Tarea $tarea): RedirectResponse
    {
        $this->verificarPertenencia($tarea);

        if (CicloTarea::esFinal($tarea->estado)) {
            return back()->with('error', 'No se puede editar una tarea realizada o cancelada.');
        }

        $tarea->update($request->datosClasificados());

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

        $reglas = [
            'nota' => ['required', 'string', 'max:1000'],
            'evidencias' => ['nullable', 'array', 'max:'.Evidencias::MAXIMO],
            'evidencias.*' => ['file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
            'cerrar_sin_esperar' => ['nullable', 'boolean'],
        ];
        if ($destino === 'realizada') {
            $this->authorize('tareas.cerrar');
        }
        $datos = $request->validate($reglas);

        // En tareas compartidas, nadie cierra hasta que cada participante haya hecho algo.
        // Solo el responsable principal puede cerrar sin esperar, y queda anotado.
        $sinEsperar = collect();
        if ($destino === 'realizada') {
            $pendientes = $tarea->participantesPendientes();
            if ($pendientes->isNotEmpty()) {
                $nombres = $pendientes->pluck('nombre_completo')->join(', ');
                if (! $request->boolean('cerrar_sin_esperar')) {
                    throw ValidationException::withMessages([
                        'estado' => "Faltan avances de: {$nombres}. Cada participante debe registrar su avance antes de cerrar la tarea.",
                    ]);
                }
                if (! $this->esPrincipal($tarea, $request->user()->id)) {
                    throw ValidationException::withMessages([
                        'estado' => 'Solo el responsable principal puede cerrar la tarea sin esperar a los demás.',
                    ]);
                }
                $sinEsperar = $pendientes;
            }
        }

        $nota = $datos['nota'];
        if ($sinEsperar->isNotEmpty()) {
            $nota .= ' (Cerrada sin esperar a: '.$sinEsperar->pluck('nombre_completo')->join(', ').')';
        }

        DB::transaction(function () use ($tarea, $origen, $destino, $nota, $request): void {
            $tarea->estado = $destino;
            match ($destino) {
                'en_proceso' => $tarea->fill(['iniciada_at' => now(), 'nota_avance' => $nota]),
                'realizada' => $tarea->fill(['realizada_at' => now(), 'nota_cierre' => $nota]),
                'cancelada' => $tarea->fill(['cancelada_at' => now(), 'nota_cancelacion' => $nota]),
                default => null,
            };
            $tarea->save();

            $movimiento = $tarea->historialEstados()->create([
                'estado_origen' => $origen,
                'estado_destino' => $destino,
                'cambiado_por' => $request->user()->id,
                'nota' => $nota,
                'cambiado_at' => now(),
            ]);

            Evidencias::adjuntar(
                $tarea,
                $movimiento,
                $request->file('evidencias', []),
                $request->user()->id,
                'Evidencia: '.CicloTarea::etiqueta($destino),
            );
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

    /**
     * Avance de un participante: registra su nota y fotos sin cambiar el estado
     * de la tarea. Cada responsable deja su propia huella en la bitácora.
     */
    public function avance(Request $request, Tarea $tarea): RedirectResponse
    {
        $this->verificarPertenencia($tarea);

        $usuario = $request->user();
        $esParticipante = $tarea->asignaciones()->whereNull('desasignado_at')->where('usuario_id', $usuario->id)->exists();
        abort_unless($esParticipante || $usuario->can('tareas.asignar'), 403);

        if ($tarea->estado !== 'en_proceso') {
            return back()->with('error', 'Solo se registran avances mientras la tarea está en proceso.');
        }

        $datos = $request->validate([
            'nota' => ['required', 'string', 'max:1000'],
            'evidencias' => ['nullable', 'array', 'max:'.Evidencias::MAXIMO],
            'evidencias.*' => ['file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        DB::transaction(function () use ($tarea, $datos, $request, $usuario): void {
            $movimiento = $tarea->historialEstados()->create([
                'estado_origen' => $tarea->estado,
                'estado_destino' => $tarea->estado,
                'cambiado_por' => $usuario->id,
                'nota' => $datos['nota'],
                'cambiado_at' => now(),
            ]);

            Evidencias::adjuntar($tarea, $movimiento, $request->file('evidencias', []), $usuario->id, 'Evidencia: Avance');
        });

        $this->notificarResponsables(
            $tarea,
            $usuario->id,
            'tarea_modificada',
            "Nuevo avance en: {$tarea->titulo}",
            "{$usuario->nombre_completo} registró su avance.",
        );

        return back()->with('exito', 'Avance registrado.');
    }

    /** Convierte un movimiento del historial (estado o avance) al formato de la bitácora. */
    private function movimientoBitacora(HistorialEstadoTarea $h): array
    {
        $avance = $h->estado_origen !== null && $h->estado_origen === $h->estado_destino;
        $cambios = $avance ? [] : [[
            'campo' => 'estado',
            'antes' => $h->estado_origen ? CicloTarea::etiqueta($h->estado_origen) : null,
            'despues' => CicloTarea::etiqueta($h->estado_destino),
        ]];

        return [
            'id' => 'h'.$h->id,
            'accion' => $avance ? 'avance' : 'estado',
            'etiqueta' => $avance ? 'Registró avance' : ($h->estado_origen ? 'Cambió estado' : 'Creó la tarea'),
            'usuario' => $h->cambiadoPor?->nombre_completo ?? 'Sistema',
            'fecha' => $h->cambiado_at?->toISOString(),
            'nota' => $h->nota,
            'cambios' => $cambios,
        ];
    }

    private function esPrincipal(Tarea $tarea, int $usuarioId): bool
    {
        return $tarea->asignaciones()->whereNull('desasignado_at')->where('es_principal', true)->where('usuario_id', $usuarioId)->exists();
    }

    /**
     * Qué ha hecho cada responsable activo: su último avance (con fotos) y cuántas
     * acciones lleva registradas. Sirve para ver de un vistazo quién ya avanzó.
     *
     * @return list<array<string, mixed>>
     */
    private function resumenAvances(Tarea $tarea): array
    {
        return $tarea->asignaciones()->whereNull('desasignado_at')->with('usuario:id,nombre,apellidos')->get()
            ->map(function ($a) use ($tarea) {
                $ultimo = $tarea->historialEstados()
                    ->where('cambiado_por', $a->usuario_id)
                    ->whereColumn('estado_origen', 'estado_destino')
                    ->with('evidencias')
                    ->latest('cambiado_at')
                    ->first();

                $ultimo?->evidencias->each(fn ($doc) => $doc->setAttribute('url', $doc->url()));

                return [
                    'usuario_id' => $a->usuario_id,
                    'nombre' => $a->usuario?->nombre_completo,
                    'es_principal' => (bool) $a->es_principal,
                    'acciones' => $tarea->historialEstados()->where('cambiado_por', $a->usuario_id)->count(),
                    'ultimo' => $ultimo ? [
                        'nota' => $ultimo->nota,
                        'fecha' => $ultimo->cambiado_at?->toISOString(),
                        'evidencias' => $ultimo->evidencias,
                    ] : null,
                ];
            })
            ->values()
            ->all();
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
