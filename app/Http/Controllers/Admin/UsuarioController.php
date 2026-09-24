<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GuardarUsuarioRequest;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use App\Models\TipoMantenimiento;
use App\Models\Usuario;
use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

/**
 * Administración de usuarios. Especificación v2.0 §5.3-5.4 / RF-010..014.
 */
class UsuarioController extends Controller
{
    /** Columnas por las que se permite ordenar el listado. */
    private const ORDENABLES = ['nombre', 'email', 'ultimo_acceso_at', 'created_at'];

    /** Registros por página del listado. */
    private const POR_PAGINA = 15;

    public function index(Request $request): Response
    {
        $this->authorize('usuarios.ver');

        $orden = in_array($request->query('orden'), self::ORDENABLES, true) ? $request->query('orden') : 'nombre';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $texto = fn (string $clave): ?string => filled($request->query($clave)) ? trim((string) $request->query($clave)) : null;

        $usuarios = Usuario::query()
            ->with(['sucursal:id,nombre', 'roles:id,name'])
            // Filtros por columna
            ->when($texto('nombre'), fn (Builder $q, $v) => $q->where(fn (Builder $s) => $s
                ->where('nombre', 'like', "%{$v}%")
                ->orWhere('apellidos', 'like', "%{$v}%")))
            ->when($texto('email'), fn (Builder $q, $v) => $q->where('email', 'like', "%{$v}%"))
            ->when($request->integer('sucursal_id'), fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->when($request->filled('rol'), fn (Builder $q) => $q->role($request->query('rol')))
            ->when(
                $request->query('estado') === 'inactivo',
                fn (Builder $q) => $q->onlyTrashed(),
            )
            ->when($request->filled('registrado_por'), fn (Builder $q) => $q->whereIn(
                'id',
                Auditoria::idsCreadosPor(Usuario::class, trim((string) $request->query('registrado_por'))),
            ))
            ->orderBy($orden, $dir)
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        $creadores = Auditoria::creadoPorMasivo(Usuario::class, $usuarios->pluck('id'));
        $usuarios->through(fn (Usuario $u) => [
            'id' => $u->id,
            'nombre_completo' => $u->nombre_completo,
            'email' => $u->email,
            'telefono' => $u->telefono,
            'sucursal' => $u->sucursal?->nombre,
            'roles' => $u->roles->pluck('name'),
            'ultimo_acceso_at' => $u->ultimo_acceso_at,
            'estado' => $u->estado,
            'creado_por' => $creadores[$u->id]['usuario'] ?? null,
            'creado_en' => $creadores[$u->id]['fecha'] ?? null,
        ]);

        return Inertia::render('Admin/Usuarios/Index', [
            'usuarios' => $usuarios,
            'filtros' => $request->only(['nombre', 'email', 'sucursal_id', 'rol', 'estado', 'registrado_por']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
            'catalogos' => [
                'sucursales' => Sucursal::orderBy('nombre')->get(['id', 'nombre']),
                'roles' => Role::orderBy('name')->pluck('name'),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('usuarios.crear');

        return Inertia::render('Admin/Usuarios/Form', [
            'usuario' => null,
            'catalogos' => $this->catalogos(),
        ]);
    }

    public function store(GuardarUsuarioRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except(['roles', 'password', 'password_confirmation', 'especialidades_equipo', 'especialidades_mantenimiento']);
        $datos['password'] = Hash::make($request->input('password'));

        $usuario = Usuario::create($datos);
        $usuario->syncRoles($request->input('roles', []));
        $usuario->especialidadesEquipo()->sync($request->input('especialidades_equipo', []));
        $usuario->especialidadesMantenimiento()->sync($request->input('especialidades_mantenimiento', []));

        return redirect()->route('usuarios.show', $usuario)->with('exito', 'Usuario creado.');
    }

    public function show(Usuario $usuario): Response
    {
        $this->authorize('usuarios.ver');

        $usuario->load(['sucursal:id,nombre', 'roles:id,name', 'especialidadesEquipo:id,nombre', 'especialidadesMantenimiento:id,nombre']);

        return Inertia::render('Admin/Usuarios/Show', [
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'apellidos' => $usuario->apellidos,
                'nombre_completo' => $usuario->nombre_completo,
                'email' => $usuario->email,
                'telefono' => $usuario->telefono,
                'sucursal' => $usuario->sucursal?->only(['id', 'nombre']),
                'estado' => $usuario->estado,
                'roles' => $usuario->roles->pluck('name'),
                'especialidades_equipo' => $usuario->especialidadesEquipo->pluck('nombre'),
                'especialidades_mantenimiento' => $usuario->especialidadesMantenimiento->pluck('nombre'),
                'ultimo_acceso_at' => $usuario->ultimo_acceso_at,
                'email_verified_at' => $usuario->email_verified_at,
                'created_at' => $usuario->created_at,
            ],
            'sello' => $usuario->selloAuditoria(),
            'actividad' => $usuario->registrosAuditoria()
                ->latest('created_at')
                ->limit(30)
                ->get(['id', 'accion', 'modulo', 'created_at']),
        ]);
    }

    public function edit(Usuario $usuario): Response
    {
        $this->authorize('usuarios.editar');

        $usuario->load(['roles:id,name', 'especialidadesEquipo:id', 'especialidadesMantenimiento:id']);

        return Inertia::render('Admin/Usuarios/Form', [
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'apellidos' => $usuario->apellidos,
                'email' => $usuario->email,
                'telefono' => $usuario->telefono,
                'sucursal_id' => $usuario->sucursal_id,
                'estado' => $usuario->estado,
                'roles' => $usuario->roles->pluck('name'),
                'especialidades_equipo' => $usuario->especialidadesEquipo->pluck('id'),
                'especialidades_mantenimiento' => $usuario->especialidadesMantenimiento->pluck('id'),
            ],
            'catalogos' => $this->catalogos(),
        ]);
    }

    public function update(GuardarUsuarioRequest $request, Usuario $usuario): RedirectResponse
    {
        $datos = $request->safe()->except(['roles', 'password', 'password_confirmation', 'especialidades_equipo', 'especialidades_mantenimiento']);

        if ($request->filled('password')) {
            $datos['password'] = Hash::make($request->input('password'));
        }

        $usuario->update($datos);
        $usuario->syncRoles($request->input('roles', []));
        $usuario->especialidadesEquipo()->sync($request->input('especialidades_equipo', []));
        $usuario->especialidadesMantenimiento()->sync($request->input('especialidades_mantenimiento', []));

        return redirect()->route('usuarios.show', $usuario)->with('exito', 'Usuario actualizado.');
    }

    /** Baja lógica del usuario. RF-011. */
    public function destroy(Request $request, Usuario $usuario): RedirectResponse
    {
        $this->authorize('usuarios.desactivar');

        abort_if($usuario->id === $request->user()->id, 403, 'No puedes desactivar tu propia cuenta.');

        $usuario->update(['estado' => 'inactivo']);
        $usuario->delete();

        return redirect()->route('usuarios.index')->with('exito', 'Usuario desactivado.');
    }

    /** Reactiva un usuario dado de baja. */
    public function restore(int $usuario): RedirectResponse
    {
        $this->authorize('usuarios.editar');

        $registro = Usuario::withTrashed()->findOrFail($usuario);
        $registro->restore();
        $registro->update(['estado' => 'activo']);

        return back()->with('exito', 'Usuario reactivado.');
    }

    /** Envía un enlace de restablecimiento de contraseña. RF-012. */
    public function restablecerContrasena(Usuario $usuario): RedirectResponse
    {
        $this->authorize('usuarios.editar');

        $estado = Password::sendResetLink(['email' => $usuario->email]);

        return back()->with(
            $estado === Password::RESET_LINK_SENT ? 'exito' : 'error',
            $estado === Password::RESET_LINK_SENT
                ? 'Se envió el enlace de restablecimiento.'
                : 'No se pudo enviar el enlace.',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogos(): array
    {
        return [
            'sucursales' => Sucursal::orderBy('nombre')->get(['id', 'nombre']),
            'roles' => Role::orderBy('name')->get(['id', 'name']),
            'tipos_equipo' => TipoEquipo::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'tipos_mantenimiento' => TipoMantenimiento::activos()->orderBy('nombre')->get(['id', 'nombre']),
        ];
    }
}
