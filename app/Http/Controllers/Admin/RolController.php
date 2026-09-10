<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Administración de roles y permisos. Especificación v2.0 §4 / RF-004.
 */
class RolController extends Controller
{
    public function index(): Response
    {
        $this->authorize('roles.ver');

        return Inertia::render('Admin/Roles/Index', [
            'roles' => Role::withCount(['users', 'permissions'])->orderBy('name')->get(),
            'permisos' => $this->permisosAgrupados(),
        ]);
    }

    public function show(Role $role): Response
    {
        $this->authorize('roles.ver');

        return Inertia::render('Admin/Roles/Show', [
            'rol' => $role->load('permissions:id,name'),
            'permisos' => $this->permisosAgrupados(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('roles.crear');

        $datos = $request->validate([
            'name' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/', Rule::unique('roles', 'name')],
            'permisos' => ['array'],
            'permisos.*' => ['string', Rule::exists('permisos', 'name')],
        ]);

        $rol = Role::create(['name' => Str::of($datos['name'])->lower()->value(), 'guard_name' => 'web']);
        $rol->syncPermissions($datos['permisos'] ?? []);

        return back()->with('exito', 'Rol creado.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('roles.editar');

        $datos = $request->validate([
            'permisos' => ['present', 'array'],
            'permisos.*' => ['string', Rule::exists('permisos', 'name')],
        ]);

        if ($role->name === 'superadministrador') {
            return back()->with('error', 'El rol superadministrador no puede modificarse.');
        }

        $role->syncPermissions($datos['permisos']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('exito', 'Permisos del rol actualizados.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('roles.editar');

        if (in_array($role->name, ['superadministrador', 'supervisor', 'tecnico', 'usuario_basico', 'auditor'], true)) {
            return back()->with('error', 'No se puede eliminar un rol base del sistema.');
        }
        if ($role->users()->exists()) {
            return back()->with('error', 'El rol tiene usuarios asignados.');
        }

        $role->delete();

        return back()->with('exito', 'Rol eliminado.');
    }

    /**
     * Permisos agrupados por módulo para la matriz de asignación.
     *
     * @return array<string, list<string>>
     */
    private function permisosAgrupados(): array
    {
        return Permission::orderBy('name')->pluck('name')
            ->groupBy(fn (string $p) => Str::before($p, '.'))
            ->map(fn ($grupo) => $grupo->values()->all())
            ->all();
    }
}
