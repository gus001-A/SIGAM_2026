<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Proveedor;
use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            ->when($request->filled('registrado_por'), fn (Builder $q) => $q->whereIn(
                'id',
                Auditoria::idsCreadosPor(Proveedor::class, trim((string) $request->query('registrado_por'))),
            ))
            ->orderBy($orden, $dir)
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        $creadores = Auditoria::creadoPorMasivo(Proveedor::class, $proveedores->pluck('id'));
        $proveedores->through(fn (Proveedor $p) => [
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
            'creado_por' => $creadores[$p->id]['usuario'] ?? null,
            'creado_en' => $creadores[$p->id]['fecha'] ?? null,
        ]);

        return Inertia::render('Inventario/Proveedores/Index', [
            'proveedores' => $proveedores,
            'filtros' => $request->only(['razon_social', 'rfc', 'contacto', 'especialidad', 'estado', 'registrado_por']),
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

        $datos = $this->validar($request);
        $request->validate([
            'documentos' => ['nullable', 'array', 'max:5'],
            'documentos.*' => ['file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        $proveedor = DB::transaction(function () use ($datos, $request) {
            $proveedor = Proveedor::create($datos);

            foreach ($request->file('documentos', []) as $archivo) {
                $ruta = $archivo->store('documentos/'.now()->format('Y/m'), 'local');

                $documento = Documento::create([
                    'disco' => 'local',
                    'ruta' => $ruta,
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'titulo' => 'Contrato / documento del proveedor',
                    'categoria' => 'contrato',
                    'tipo_mime' => $archivo->getClientMimeType(),
                    'tamano' => $archivo->getSize(),
                    'checksum' => hash_file('sha256', $archivo->getRealPath()),
                    'visibilidad' => 'privado',
                    'subido_por' => $request->user()->id,
                ]);

                $proveedor->documentos()->attach($documento->id, ['rol' => 'contrato']);
            }

            return $proveedor;
        });

        return redirect()->route('proveedores.show', $proveedor)->with('exito', 'Proveedor creado.');
    }

    public function show(Proveedor $proveedor): Response
    {
        $this->authorize('proveedores.ver');

        $proveedor->load(['documentos', 'equipos:id,codigo_activo,descripcion,proveedor_id']);

        return Inertia::render('Inventario/Proveedores/Show', ['proveedor' => $proveedor, 'sello' => $proveedor->selloAuditoria()]);
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
        $request->merge(Proveedor::normalizarMayusculas($request->all()));

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
