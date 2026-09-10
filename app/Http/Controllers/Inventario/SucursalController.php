<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventario\GuardarSucursalRequest;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sucursales. Especificación v2.0 §5.5 / RF-020..022.
 */
class SucursalController extends Controller
{
    /** Columnas por las que se permite ordenar el listado. */
    private const ORDENABLES = ['codigo', 'nombre', 'equipos_count', 'valor_activos', 'created_at'];

    /** Registros por página del listado. */
    private const POR_PAGINA = 15;

    public function index(Request $request): Response
    {
        $this->authorize('sucursales.ver');

        $orden = in_array($request->query('orden'), self::ORDENABLES, true) ? $request->query('orden') : 'nombre';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $texto = fn (string $clave): ?string => filled($request->query($clave)) ? trim((string) $request->query($clave)) : null;

        $sucursales = Sucursal::query()
            ->withCount(['equipos', 'ubicaciones', 'usuarios'])
            ->withSum('equipos as valor_activos', 'valor_adquisicion')
            ->with('responsable:id,nombre,apellidos')
            // Filtros por columna
            ->when($texto('codigo'), fn (Builder $q, $v) => $q->where('codigo', 'like', "%{$v}%"))
            ->when($texto('nombre'), fn (Builder $q, $v) => $q->where('nombre', 'like', "%{$v}%"))
            ->when($texto('direccion'), fn (Builder $q, $v) => $q->where('direccion', 'like', "%{$v}%"))
            ->when($request->integer('responsable_id'), fn (Builder $q, $v) => $q->where('responsable_id', $v))
            // Las sucursales dadas de baja quedan con SoftDeletes; se listan al filtrar por "inactivo".
            ->when(
                $request->query('estado') === 'inactivo',
                fn (Builder $q) => $q->onlyTrashed(),
            )
            ->orderBy($orden, $dir)
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Sucursal $s) => [
                'id' => $s->id,
                'codigo' => $s->codigo,
                'nombre' => $s->nombre,
                'direccion' => $s->direccion,
                'telefono' => $s->telefono,
                'correo' => $s->correo,
                'responsable' => $s->responsable?->nombre_completo,
                'equipos_count' => $s->equipos_count,
                'ubicaciones_count' => $s->ubicaciones_count,
                'usuarios_count' => $s->usuarios_count,
                'valor_activos' => (float) $s->valor_activos,
                'estado' => $s->estado,
            ]);

        return Inertia::render('Inventario/Sucursales/Index', [
            'sucursales' => $sucursales,
            'filtros' => $request->only(['codigo', 'nombre', 'direccion', 'responsable_id', 'estado']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
            'catalogos' => ['responsables' => $this->responsables()],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('sucursales.crear');

        return Inertia::render('Inventario/Sucursales/Form', [
            'sucursal' => null,
            'responsables' => $this->responsables(),
        ]);
    }

    public function store(GuardarSucursalRequest $request): RedirectResponse
    {
        $sucursal = Sucursal::create($request->validated());

        return redirect()
            ->route('sucursales.show', $sucursal)
            ->with('exito', 'Sucursal creada.');
    }

    public function show(Sucursal $sucursal): Response
    {
        $this->authorize('sucursales.ver');

        $sucursal->load([
            'responsable:id,nombre,apellidos',
            'ubicaciones' => fn ($q) => $q->whereNull('padre_id')->withCount('hijas')->orderBy('nombre'),
            'documentos',
        ]);
        $sucursal->loadCount(['equipos', 'usuarios', 'ubicaciones', 'solicitudes', 'mantenimientos']);

        return Inertia::render('Inventario/Sucursales/Show', [
            'sucursal' => [
                'id' => $sucursal->id,
                'codigo' => $sucursal->codigo,
                'nombre' => $sucursal->nombre,
                'direccion' => $sucursal->direccion,
                'telefono' => $sucursal->telefono,
                'correo' => $sucursal->correo,
                'notas' => $sucursal->notas,
                'estado' => $sucursal->estado,
                'responsable' => $sucursal->responsable
                    ? ['id' => $sucursal->responsable->id, 'nombre' => $sucursal->responsable->nombre_completo]
                    : null,
                'equipos_count' => $sucursal->equipos_count,
                'usuarios_count' => $sucursal->usuarios_count,
                'ubicaciones_count' => $sucursal->ubicaciones_count,
                'solicitudes_count' => $sucursal->solicitudes_count,
                'mantenimientos_count' => $sucursal->mantenimientos_count,
                'ubicaciones' => $sucursal->ubicaciones->map(fn ($u) => [
                    'id' => $u->id,
                    'codigo' => $u->codigo,
                    'nombre' => $u->nombre,
                    'hijas_count' => $u->hijas_count,
                ]),
                'documentos' => $sucursal->documentos,
            ],
            'valorActivos' => (float) $sucursal->equipos()->sum('valor_adquisicion'),
        ]);
    }

    public function edit(Sucursal $sucursal): Response
    {
        $this->authorize('sucursales.editar');

        return Inertia::render('Inventario/Sucursales/Form', [
            'sucursal' => $sucursal,
            'responsables' => $this->responsables(),
        ]);
    }

    public function update(GuardarSucursalRequest $request, Sucursal $sucursal): RedirectResponse
    {
        $sucursal->update($request->validated());

        return redirect()
            ->route('sucursales.show', $sucursal)
            ->with('exito', 'Sucursal actualizada.');
    }

    /** Baja lógica para conservar historial (§7). */
    public function destroy(Sucursal $sucursal): RedirectResponse
    {
        $this->authorize('sucursales.desactivar');

        $sucursal->update(['estado' => 'inactivo']);
        $sucursal->delete();

        return redirect()
            ->route('sucursales.index')
            ->with('exito', 'Sucursal desactivada.');
    }

    public function restore(int $sucursal): RedirectResponse
    {
        $this->authorize('sucursales.editar');

        $registro = Sucursal::withTrashed()->findOrFail($sucursal);
        $registro->restore();
        $registro->update(['estado' => 'activo']);

        return back()->with('exito', 'Sucursal reactivada.');
    }

    /** @return Collection<int, array{value:int,label:string}> */
    private function responsables(): Collection
    {
        return Usuario::query()
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'apellidos'])
            ->map(fn (Usuario $u) => ['value' => $u->id, 'label' => $u->nombre_completo]);
    }
}
