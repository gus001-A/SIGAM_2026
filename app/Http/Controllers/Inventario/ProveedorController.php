<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Proveedores / prestadores de servicio. Especificación v2.0 §5.15.
 */
class ProveedorController extends Controller
{
    /** Columnas por las que se permite ordenar el listado. */
    private const ORDENABLES = ['razon_social', 'rfc', 'especialidad', 'equipos_count', 'created_at'];

    /** Registros por página del listado. */
    private const POR_PAGINA = 15;

    public function index(Request $request): Response
    {
        $this->authorize('proveedores.ver');

        $orden = in_array($request->query('orden'), self::ORDENABLES, true) ? $request->query('orden') : 'razon_social';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $texto = fn (string $clave): ?string => filled($request->query($clave)) ? trim((string) $request->query($clave)) : null;

        $proveedores = Proveedor::query()
            ->withCount('equipos')
            // Filtros por columna
            ->when($texto('razon_social'), fn (Builder $q, $v) => $q->where(fn (Builder $s) => $s
                ->where('razon_social', 'like', "%{$v}%")
                ->orWhere('nombre_comercial', 'like', "%{$v}%")))
            ->when($texto('rfc'), fn (Builder $q, $v) => $q->where('rfc', 'like', "%{$v}%"))
            ->when($texto('contacto'), fn (Builder $q, $v) => $q->where(fn (Builder $s) => $s
                ->where('contacto', 'like', "%{$v}%")
                ->orWhere('telefono', 'like', "%{$v}%")
                ->orWhere('correo', 'like', "%{$v}%")))
            ->when($texto('especialidad'), fn (Builder $q, $v) => $q->where('especialidad', 'like', "%{$v}%"))
            ->when(
                $request->query('estado') === 'inactivo',
                fn (Builder $q) => $q->onlyTrashed(),
            )
            ->orderBy($orden, $dir)
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Proveedor $p) => [
                'id' => $p->id,
                'razon_social' => $p->razon_social,
                'nombre_comercial' => $p->nombre_comercial,
                'rfc' => $p->rfc,
                'contacto' => $p->contacto,
                'telefono' => $p->telefono,
                'correo' => $p->correo,
                'especialidad' => $p->especialidad,
                'equipos_count' => $p->equipos_count,
                'estado' => $p->estado,
            ]);

        return Inertia::render('Inventario/Proveedores/Index', [
            'proveedores' => $proveedores,
            'filtros' => $request->only(['razon_social', 'rfc', 'contacto', 'especialidad', 'estado']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('proveedores.crear');

        return Inertia::render('Inventario/Proveedores/Form', ['proveedor' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('proveedores.crear');

        $proveedor = Proveedor::create($this->validar($request));

        return redirect()->route('proveedores.show', $proveedor)->with('exito', 'Proveedor creado.');
    }

    public function show(Proveedor $proveedor): Response
    {
        $this->authorize('proveedores.ver');

        $proveedor->load(['documentos', 'equipos:id,codigo_activo,descripcion,proveedor_id']);

        return Inertia::render('Inventario/Proveedores/Show', ['proveedor' => $proveedor]);
    }

    public function edit(Proveedor $proveedor): Response
    {
        $this->authorize('proveedores.editar');

        return Inertia::render('Inventario/Proveedores/Form', ['proveedor' => $proveedor]);
    }

    public function update(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $this->authorize('proveedores.editar');

        $proveedor->update($this->validar($request, $proveedor));

        return redirect()->route('proveedores.show', $proveedor)->with('exito', 'Proveedor actualizado.');
    }

    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        $this->authorize('proveedores.desactivar');

        $proveedor->update(['estado' => 'inactivo']);
        $proveedor->delete();

        return redirect()->route('proveedores.index')->with('exito', 'Proveedor desactivado.');
    }

    public function restore(int $proveedor): RedirectResponse
    {
        $this->authorize('proveedores.editar');

        $registro = Proveedor::withTrashed()->findOrFail($proveedor);
        $registro->restore();
        $registro->update(['estado' => 'activo']);

        return back()->with('exito', 'Proveedor reactivado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Proveedor $proveedor = null): array
    {
        return $request->validate([
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:20', Rule::unique('proveedores', 'rfc')->ignore($proveedor?->id)],
            'contacto' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'digits:10'],
            'correo' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'especialidad' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
            'notas' => ['nullable', 'string'],
        ]);
    }
}
