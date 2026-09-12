<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Models\Norma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Normas / procedimientos aplicables. Especificación v2.0 §5.14.
 */
class NormaController extends Controller
{
    /** Columnas por las que se permite ordenar el listado. */
    private const ORDENABLES = ['codigo', 'nombre', 'fecha_vigencia', 'fecha_revision', 'equipos_count', 'created_at'];

    /** Registros por página del listado. */
    private const POR_PAGINA = 15;

    public function index(Request $request): Response
    {
        $this->authorize('normas.ver');

        $orden = in_array($request->query('orden'), self::ORDENABLES, true) ? $request->query('orden') : 'codigo';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $texto = fn (string $clave): ?string => filled($request->query($clave)) ? trim((string) $request->query($clave)) : null;

        $normas = Norma::query()
            ->with('documento:id,nombre_original,ruta,disco')
            ->withCount(['equipos', 'planes', 'mantenimientos'])
            // Filtros por columna
            ->when($texto('codigo'), fn (Builder $q, $v) => $q->where('codigo', 'like', "%{$v}%"))
            ->when($texto('nombre'), fn (Builder $q, $v) => $q->where('nombre', 'like', "%{$v}%"))
            ->when($texto('version'), fn (Builder $q, $v) => $q->where('version', 'like', "%{$v}%"))
            ->when(
                $request->query('estado') === 'inactivo',
                fn (Builder $q) => $q->onlyTrashed(),
            )
            ->orderBy($orden, $dir)
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Norma $n) => [
                'id' => $n->id,
                'codigo' => $n->codigo,
                'nombre' => $n->nombre,
                'version' => $n->version,
                'fecha_vigencia' => $n->fecha_vigencia?->toDateString(),
                'fecha_revision' => $n->fecha_revision?->toDateString(),
                'revision_vencida' => $n->fecha_revision !== null && $n->fecha_revision->isPast(),
                'tiene_documento' => $n->documento_id !== null,
                'equipos_count' => $n->equipos_count,
                'planes_count' => $n->planes_count,
                'mantenimientos_count' => $n->mantenimientos_count,
                'estado' => $n->estado,
            ]);

        return Inertia::render('Normas/Index', [
            'normas' => $normas,
            'filtros' => $request->only(['codigo', 'nombre', 'version', 'estado']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('normas.crear');

        return Inertia::render('Normas/Form', ['norma' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('normas.crear');

        $norma = Norma::create($this->validar($request));

        return redirect()->route('normas.show', $norma)->with('exito', 'Norma registrada.');
    }

    public function show(Norma $norma): Response
    {
        $this->authorize('normas.ver');

        $norma->load(['documento', 'documentos', 'equipos:id,codigo_activo,descripcion']);
        $norma->loadCount(['equipos', 'planes', 'mantenimientos']);

        return Inertia::render('Normas/Show', ['norma' => $norma]);
    }

    public function edit(Norma $norma): Response
    {
        $this->authorize('normas.editar');

        return Inertia::render('Normas/Form', ['norma' => $norma]);
    }

    public function update(Request $request, Norma $norma): RedirectResponse
    {
        $this->authorize('normas.editar');

        $norma->update($this->validar($request, $norma));

        return redirect()->route('normas.show', $norma)->with('exito', 'Norma actualizada.');
    }

    public function destroy(Norma $norma): RedirectResponse
    {
        $this->authorize('normas.desactivar');

        $norma->update(['estado' => 'inactivo']);
        $norma->delete();

        return redirect()->route('normas.index')->with('exito', 'Norma desactivada.');
    }

    public function restore(int $norma): RedirectResponse
    {
        $this->authorize('normas.editar');

        $registro = Norma::withTrashed()->findOrFail($norma);
        $registro->restore();
        $registro->update(['estado' => 'activo']);

        return back()->with('exito', 'Norma reactivada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Norma $norma = null): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:80', Rule::unique('normas', 'codigo')->ignore($norma?->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:40'],
            'descripcion' => ['nullable', 'string'],
            'fecha_vigencia' => ['nullable', 'date'],
            'fecha_revision' => ['nullable', 'date', 'after_or_equal:fecha_vigencia'],
            'documento_id' => ['nullable', 'integer', Rule::exists('documentos', 'id')],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ]);
    }
}
