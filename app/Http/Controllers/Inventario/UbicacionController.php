<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventario\GuardarUbicacionRequest;
use App\Models\Sucursal;
use App\Models\TipoUbicacion;
use App\Models\Ubicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ubicaciones jerárquicas. Especificación v2.0 §5.6 / RF-023..025.
 */
class UbicacionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('ubicaciones.ver');

        $sucursalId = $request->integer('sucursal_id') ?: Sucursal::query()->orderBy('nombre')->value('id');

        $ubicaciones = Ubicacion::query()
            ->where('sucursal_id', $sucursalId)
            ->with('tipo:id,nombre')
            ->withCount(['hijas', 'equipos'])
            ->orderBy('padre_id')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Inventario/Ubicaciones/Index', [
            'sucursales' => Sucursal::orderBy('nombre')->get(['id', 'nombre']),
            'sucursalSeleccionada' => $sucursalId,
            'arbol' => $this->armarArbol($ubicaciones),
            'tipos' => TipoUbicacion::activos()->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(GuardarUbicacionRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['profundidad'] = $this->calcularProfundidad($datos['padre_id'] ?? null);

        $ubicacion = Ubicacion::create($datos);
        $ubicacion->update(['ruta' => $this->calcularRuta($ubicacion)]);

        return back()->with('exito', 'Ubicación creada.');
    }

    public function update(GuardarUbicacionRequest $request, Ubicacion $ubicacion): RedirectResponse
    {
        $datos = $request->validated();
        $datos['profundidad'] = $this->calcularProfundidad($datos['padre_id'] ?? null);

        $ubicacion->update($datos);
        $ubicacion->update(['ruta' => $this->calcularRuta($ubicacion)]);

        return back()->with('exito', 'Ubicación actualizada.');
    }

    public function destroy(Ubicacion $ubicacion): RedirectResponse
    {
        $this->authorize('ubicaciones.desactivar');

        if ($ubicacion->hijas()->exists()) {
            return back()->with('error', 'No se puede desactivar: la ubicación tiene sububicaciones.');
        }
        if ($ubicacion->equipos()->exists()) {
            return back()->with('error', 'No se puede desactivar: la ubicación tiene equipos asignados.');
        }

        $ubicacion->update(['estado' => 'inactivo']);
        $ubicacion->delete();

        return back()->with('exito', 'Ubicación desactivada.');
    }

    /**
     * @param  Collection<int, Ubicacion>  $ubicaciones
     * @return array<int, mixed>
     */
    private function armarArbol($ubicaciones, ?int $padreId = null): array
    {
        return $ubicaciones
            ->where('padre_id', $padreId)
            ->map(fn (Ubicacion $u) => [
                'id' => $u->id,
                'padre_id' => $u->padre_id,
                'tipo_id' => $u->tipo_id,
                'codigo' => $u->codigo,
                'nombre' => $u->nombre,
                'descripcion' => $u->descripcion,
                'tipo' => $u->tipo?->nombre,
                'estado' => $u->estado,
                'equipos_count' => $u->equipos_count,
                'hijas_count' => $u->hijas_count,
                'hijas' => $this->armarArbol($ubicaciones, $u->id),
            ])
            ->values()
            ->all();
    }

    private function calcularProfundidad(?int $padreId): int
    {
        return $padreId ? ((int) Ubicacion::whereKey($padreId)->value('profundidad') + 1) : 0;
    }

    private function calcularRuta(Ubicacion $ubicacion): string
    {
        return collect([...$ubicacion->idsAncestros(), $ubicacion->id])
            ->reverse()
            ->implode('/');
    }
}
