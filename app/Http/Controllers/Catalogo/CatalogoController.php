<?php

namespace App\Http\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use App\Support\Auditoria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Base para los catálogos configurables. Especificación v2.0 §5.13:
 * CRUD + activar/desactivar con trazabilidad. Comparten el permiso `catalogos.*`.
 */
abstract class CatalogoController extends Controller
{
    /** @var class-string<Model> */
    protected string $modelo;

    /** Subcarpeta de páginas Vue, p.ej. "Catalogos/Marcas". */
    protected string $vista;

    /** Etiqueta legible del catálogo, p.ej. "Marcas". */
    protected string $titulo;

    /** Columnas contra las que corre el filtro de texto libre / por columna. */
    protected array $buscables = ['nombre'];

    /** Columnas extra por las que se permite ordenar (además de $buscables). */
    protected array $ordenablesExtra = [];

    /** Columnas de coincidencia exacta (select), p. ej. booleanas o de categoría corta. */
    protected array $filtrosExactos = [];

    /** Genera automáticamente la columna `clave` a partir del nombre al crear. */
    protected bool $generaClave = false;

    /** Registros por página del listado. */
    protected int $porPagina = 15;

    /**
     * Reglas de validación. $registro viene poblado en actualización.
     *
     * @return array<string, mixed>
     */
    abstract protected function reglas(Request $request, ?Model $registro = null): array;

    /** Hook para eager-loading / conteos en el listado. */
    protected function consulta(): Builder
    {
        return $this->modelo::query();
    }

    public function index(Request $request): Response
    {
        $this->authorize('catalogos.ver');

        $ordenables = array_merge($this->buscables, $this->ordenablesExtra, ['created_at']);
        $orden = in_array($request->query('orden'), $ordenables, true) ? $request->query('orden') : $this->buscables[0];
        $dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        // Compatibilidad: `buscar` sigue filtrando por todas las columnas buscables.
        $buscar = trim((string) $request->query('buscar', ''));

        $registros = $this->consulta()
            ->when($buscar !== '', function (Builder $q) use ($buscar): void {
                $q->where(function (Builder $sub) use ($buscar): void {
                    foreach ($this->buscables as $columna) {
                        $sub->orWhere($columna, 'like', "%{$buscar}%");
                    }
                });
            })
            // Filtros por columna: una entrada por cada columna buscable.
            ->tap(function (Builder $q) use ($request): void {
                foreach ($this->buscables as $columna) {
                    $valor = $request->query($columna);
                    if (filled($valor)) {
                        $q->where($columna, 'like', '%'.trim((string) $valor).'%');
                    }
                }
                foreach ($this->filtrosExactos as $columna) {
                    $valor = $request->query($columna);
                    if ($valor !== null && $valor !== '') {
                        $q->where($columna, $valor);
                    }
                }
            })
            ->when($request->filled('registrado_por'), function (Builder $q) use ($request): void {
                $q->whereIn('id', Auditoria::idsCreadosPor($this->modelo, trim((string) $request->query('registrado_por'))));
            })
            ->when(
                in_array($request->query('estado'), ['activo', 'inactivo'], true),
                fn (Builder $q) => $q->where('estado', $request->query('estado')),
            )
            ->orderBy($orden, $dir)
            ->paginate($this->porPagina)
            ->withQueryString();

        $creadores = Auditoria::creadoPorMasivo($this->modelo, $registros->pluck('id'));
        $registros->through(fn (Model $r) => tap($r, function (Model $r) use ($creadores): void {
            $r->setAttribute('creado_por', $creadores[$r->getKey()]['usuario'] ?? null);
            $r->setAttribute('creado_en', $creadores[$r->getKey()]['fecha'] ?? null);
        }));

        return Inertia::render("{$this->vista}/Index", [
            'titulo' => $this->titulo,
            'registros' => $registros,
            'filtros' => $request->only([...$this->buscables, ...$this->filtrosExactos, 'buscar', 'estado', 'registrado_por']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('catalogos.crear');

        $request->merge($this->modelo::normalizarMayusculas($request->all()));
        $datos = $request->validate($this->reglas($request));

        if ($this->generaClave && empty($datos['clave'])) {
            $datos['clave'] = $this->claveUnica($datos['nombre'] ?? '');
        }
        $datos['estado'] ??= 'activo';

        $registro = $this->modelo::create($datos);

        if ($this->esAltaRapida($request)) {
            return response()->json($registro);
        }

        return back()->with('exito', "{$this->titulo}: registro creado.");
    }

    public function update(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $this->authorize('catalogos.editar');

        $registro = $this->modelo::findOrFail($id);
        $request->merge($this->modelo::normalizarMayusculas($request->all()));
        $datos = $request->validate($this->reglas($request, $registro));

        $registro->update($datos);

        if ($this->esAltaRapida($request)) {
            return response()->json($registro->fresh());
        }

        return back()->with('exito', "{$this->titulo}: registro actualizado.");
    }

    /** Baja lógica: los catálogos no se borran, se desactivan (§5.13). */
    public function destroy(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $this->authorize('catalogos.desactivar');

        $this->modelo::findOrFail($id)->update(['estado' => 'inactivo']);

        if ($this->esAltaRapida($request)) {
            return response()->json(['ok' => true]);
        }

        return back()->with('exito', "{$this->titulo}: registro desactivado.");
    }

    public function activar(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $this->authorize('catalogos.editar');

        $registro = $this->modelo::findOrFail($id);
        $registro->update(['estado' => 'activo']);

        if ($this->esAltaRapida($request)) {
            return response()->json($registro->fresh());
        }

        return back()->with('exito', "{$this->titulo}: registro reactivado.");
    }

    /**
     * Las llamadas de "alta rápida" (el botón «+» junto a los selects de
     * catálogo, en cualquier formulario del sistema) se marcan explícitamente
     * con este header desde <SelectCatalogo> y responden JSON en vez de
     * redirigir — cualquier otra petición (Inertia, pruebas, etc.) se
     * comporta exactamente igual que antes.
     */
    private function esAltaRapida(Request $request): bool
    {
        return $request->header('X-Alta-Rapida') === '1';
    }

    private function claveUnica(string $nombre): string
    {
        $base = Str::slug($nombre, '_') ?: 'item';
        $clave = $base;
        $i = 2;
        while ($this->modelo::where('clave', $clave)->exists()) {
            $clave = "{$base}_{$i}";
            $i++;
        }

        return $clave;
    }
}
