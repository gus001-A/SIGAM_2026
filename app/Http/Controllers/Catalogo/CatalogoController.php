<?php

namespace App\Http\Controllers\Catalogo;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
            })
            ->when(
                in_array($request->query('estado'), ['activo', 'inactivo'], true),
                fn (Builder $q) => $q->where('estado', $request->query('estado')),
            )
            ->orderBy($orden, $dir)
            ->paginate($this->porPagina)
            ->withQueryString();

        return Inertia::render("{$this->vista}/Index", [
            'titulo' => $this->titulo,
            'registros' => $registros,
            'filtros' => $request->only([...$this->buscables, 'buscar', 'estado']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('catalogos.crear');

        $datos = $request->validate($this->reglas($request));

        if ($this->generaClave && empty($datos['clave'])) {
            $datos['clave'] = $this->claveUnica($datos['nombre'] ?? '');
        }
        $datos['estado'] ??= 'activo';

        $this->modelo::create($datos);

        return back()->with('exito', "{$this->titulo}: registro creado.");
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->authorize('catalogos.editar');

        $registro = $this->modelo::findOrFail($id);
        $datos = $request->validate($this->reglas($request, $registro));

        $registro->update($datos);

        return back()->with('exito', "{$this->titulo}: registro actualizado.");
    }

    /** Baja lógica: los catálogos no se borran, se desactivan (§5.13). */
    public function destroy(int $id): RedirectResponse
    {
        $this->authorize('catalogos.desactivar');

        $this->modelo::findOrFail($id)->update(['estado' => 'inactivo']);

        return back()->with('exito', "{$this->titulo}: registro desactivado.");
    }

    public function activar(int $id): RedirectResponse
    {
        $this->authorize('catalogos.editar');

        $this->modelo::findOrFail($id)->update(['estado' => 'activo']);

        return back()->with('exito', "{$this->titulo}: registro reactivado.");
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
