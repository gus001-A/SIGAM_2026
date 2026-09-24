<?php

namespace App\Http\Controllers\Formato;

use App\Http\Controllers\Controller;
use App\Models\Formato;
use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Constructor de formatos / checklists configurables. Especificación v2.0 §5.16.
 * Los campos se envían anidados y se reescriben por completo en cada guardado.
 */
class FormatoController extends Controller
{
    private const TIPOS_CAMPO = ['texto', 'area_texto', 'numero', 'fecha', 'seleccion', 'checkbox', 'foto', 'firma'];

    /** Columnas por las que se permite ordenar el listado. */
    private const ORDENABLES = ['nombre', 'version', 'campos_count', 'respuestas_count', 'created_at'];

    /** Registros por página del listado. */
    private const POR_PAGINA = 15;

    public function index(Request $request): Response
    {
        $this->authorize('formatos.ver');

        $orden = in_array($request->query('orden'), self::ORDENABLES, true) ? $request->query('orden') : 'nombre';
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $texto = fn (string $clave): ?string => filled($request->query($clave)) ? trim((string) $request->query($clave)) : null;

        $formatos = Formato::query()
            ->withCount(['campos', 'respuestas'])
            ->when($texto('nombre'), fn (Builder $q, $v) => $q->where('nombre', 'like', "%{$v}%"))
            ->when($texto('version'), fn (Builder $q, $v) => $q->where('version', 'like', "%{$v}%"))
            ->when(
                $request->query('estado') === 'inactivo',
                fn (Builder $q) => $q->onlyTrashed(),
            )
            ->when($request->filled('registrado_por'), fn (Builder $q) => $q->whereIn(
                'id',
                Auditoria::idsCreadosPor(Formato::class, trim((string) $request->query('registrado_por'))),
            ))
            ->orderBy($orden, $dir)
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        $creadores = Auditoria::creadoPorMasivo(Formato::class, $formatos->pluck('id'));
        $formatos->through(fn (Formato $f) => [
            'id' => $f->id,
            'nombre' => $f->nombre,
            'descripcion' => $f->descripcion,
            'version' => $f->version,
            'campos_count' => $f->campos_count,
            'respuestas_count' => $f->respuestas_count,
            'estado' => $f->estado,
            'creado_por' => $creadores[$f->id]['usuario'] ?? null,
            'creado_en' => $creadores[$f->id]['fecha'] ?? null,
        ]);

        return Inertia::render('Formatos/Index', [
            'formatos' => $formatos,
            'filtros' => $request->only(['nombre', 'version', 'estado', 'registrado_por']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('formatos.crear');

        return Inertia::render('Formatos/Form', [
            'formato' => null,
            'tiposCampo' => self::TIPOS_CAMPO,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('formatos.crear');

        $datos = $this->validar($request);

        $formato = DB::transaction(function () use ($datos) {
            $formato = Formato::create([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'version' => $datos['version'] ?? '1.0',
                'estado' => $datos['estado'],
            ]);
            $this->sincronizarCampos($formato, $datos['campos']);

            return $formato;
        });

        return redirect()->route('formatos.edit', $formato)->with('exito', 'Formato creado.');
    }

    public function show(Formato $formato): Response
    {
        $this->authorize('formatos.ver');

        return Inertia::render('Formatos/Show', [
            'formato' => $formato->load('campos')->loadCount('respuestas'),
            'sello' => $formato->selloAuditoria(),
        ]);
    }

    public function edit(Formato $formato): Response
    {
        $this->authorize('formatos.editar');

        return Inertia::render('Formatos/Form', [
            'formato' => $formato->load('campos'),
            'tiposCampo' => self::TIPOS_CAMPO,
        ]);
    }

    public function update(Request $request, Formato $formato): RedirectResponse
    {
        $this->authorize('formatos.editar');

        $datos = $this->validar($request);

        DB::transaction(function () use ($formato, $datos): void {
            $formato->update([
                'nombre' => $datos['nombre'],
                'descripcion' => $datos['descripcion'] ?? null,
                'version' => $datos['version'] ?? $formato->version,
                'estado' => $datos['estado'],
            ]);
            $this->sincronizarCampos($formato, $datos['campos']);
        });

        return redirect()->route('formatos.edit', $formato)->with('exito', 'Formato actualizado.');
    }

    public function destroy(Formato $formato): RedirectResponse
    {
        $this->authorize('formatos.desactivar');

        $formato->update(['estado' => 'inactivo']);
        $formato->delete();

        return redirect()->route('formatos.index')->with('exito', 'Formato desactivado.');
    }

    public function restore(int $formato): RedirectResponse
    {
        $this->authorize('formatos.editar');

        $registro = Formato::withTrashed()->findOrFail($formato);
        $registro->restore();
        $registro->update(['estado' => 'activo']);

        return back()->with('exito', 'Formato reactivado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:40'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
            'campos' => ['present', 'array'],
            'campos.*.etiqueta' => ['required', 'string', 'max:255'],
            'campos.*.tipo' => ['required', Rule::in(self::TIPOS_CAMPO)],
            'campos.*.obligatorio' => ['boolean'],
            'campos.*.ayuda' => ['nullable', 'string', 'max:255'],
            'campos.*.opciones' => ['nullable', 'array'],
            'campos.*.opciones.*' => ['string', 'max:255'],
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $campos
     */
    private function sincronizarCampos(Formato $formato, array $campos): void
    {
        $formato->campos()->delete();

        foreach (array_values($campos) as $orden => $campo) {
            $formato->campos()->create([
                'tipo' => $campo['tipo'],
                'etiqueta' => $campo['etiqueta'],
                'clave' => Str::slug($campo['etiqueta'], '_') ?: "campo_{$orden}",
                'opciones' => $campo['opciones'] ?? null,
                'obligatorio' => (bool) ($campo['obligatorio'] ?? false),
                'orden' => $orden,
                'ayuda' => $campo['ayuda'] ?? null,
            ]);
        }
    }
}
