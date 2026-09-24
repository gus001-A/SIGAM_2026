<?php

namespace App\Http\Controllers\Inventario;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventario\GuardarUbicacionRequest;
use App\Models\Sucursal;
use App\Models\TipoArea;
use App\Models\TipoLimpieza;
use App\Models\TipoUbicacion;
use App\Models\Ubicacion;
use App\Support\Auditoria;
use App\Support\Folios;
use App\Support\SeleccionSucursal;
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

        $sucursalId = SeleccionSucursal::resolver($request->query('sucursal_id'));
        $modoTodas = $sucursalId === null;

        $ubicaciones = Ubicacion::query()
            ->when($sucursalId, fn ($q, $v) => $q->where('sucursal_id', $v))
            ->with(['tipo:id,nombre', 'sucursal:id,nombre', 'tipoArea:id,nombre,dias_limpieza', 'tipoLimpieza:id,nombre,frecuencia'])
            ->withCount(['hijas', 'equipos', 'solicitudes', 'mantenimientos'])
            ->orderBy('sucursal_id')
            ->orderBy('padre_id')
            ->orderBy('nombre')
            ->get();

        $creadores = Auditoria::creadoPorMasivo(Ubicacion::class, $ubicaciones->pluck('id'));

        return Inertia::render('Inventario/Ubicaciones/Index', [
            'sucursales' => Sucursal::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'sucursalSeleccionada' => $modoTodas ? SeleccionSucursal::TODAS : $sucursalId,
            'arbol' => $this->armarArbol($ubicaciones, null, $creadores),
            'tipos' => TipoUbicacion::activos()->orderBy('nombre')->get(['id', 'nombre']),
            'tiposArea' => TipoArea::activos()->orderBy('dias_limpieza')->get(['id', 'nombre', 'dias_limpieza']),
            'tiposLimpieza' => TipoLimpieza::activos()->orderBy('nombre')->get(['id', 'nombre', 'frecuencia']),
        ]);
    }

    public function store(GuardarUbicacionRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['profundidad'] = $this->calcularProfundidad($datos['padre_id'] ?? null);
        $datos['codigo'] = ($datos['codigo'] ?? null) ?: Folios::codigoUbicacion($datos['nombre'], $datos['sucursal_id']);

        $ubicacion = Ubicacion::create($datos);
        $ubicacion->update(['ruta' => $this->calcularRuta($ubicacion)]);

        return back()->with('exito', 'Ubicación creada.');
    }

    public function update(GuardarUbicacionRequest $request, Ubicacion $ubicacion): RedirectResponse
    {
        $datos = $request->validated();
        $datos['profundidad'] = $this->calcularProfundidad($datos['padre_id'] ?? null);
        $datos['codigo'] = ($datos['codigo'] ?? null) ?: Folios::codigoUbicacion($datos['nombre'], $datos['sucursal_id'], $ubicacion->id);

        $ubicacion->update($datos);
        $ubicacion->update(['ruta' => $this->calcularRuta($ubicacion)]);

        // Los hijos guardan el código del padre en su propia ruta; si este cambió, propagar.
        foreach ($ubicacion->hijas as $hija) {
            $hija->update(['ruta' => $this->calcularRuta($hija)]);
        }

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
     * @param  array<int, array{usuario: ?string, fecha: ?string}>  $creadores
     * @return array<int, mixed>
     */
    private function armarArbol($ubicaciones, ?int $padreId = null, array $creadores = []): array
    {
        return $ubicaciones
            ->where('padre_id', $padreId)
            ->map(fn (Ubicacion $u) => [
                'id' => $u->id,
                'sucursal_id' => $u->sucursal_id,
                'padre_id' => $u->padre_id,
                'tipo_id' => $u->tipo_id,
                'tipo_area_id' => $u->tipo_area_id,
                'tipo_limpieza_id' => $u->tipo_limpieza_id,
                'codigo' => $u->codigo,
                'ruta' => $u->ruta,
                'nombre' => $u->nombre,
                'descripcion' => $u->descripcion,
                'tipo' => $u->tipo?->nombre,
                'tipo_area' => $u->tipoArea ? "{$u->tipoArea->nombre} · limpieza cada {$u->tipoArea->dias_limpieza} días" : null,
                'tipo_limpieza' => $u->tipoLimpieza?->nombre,
                'sucursal' => $u->sucursal?->nombre,
                'estado' => $u->estado,
                'equipos_count' => $u->equipos_count,
                'hijas_count' => $u->hijas_count,
                'solicitudes_count' => $u->solicitudes_count,
                'mantenimientos_count' => $u->mantenimientos_count,
                'creado_por' => $creadores[$u->id]['usuario'] ?? null,
                'creado_en' => $creadores[$u->id]['fecha'] ?? null,
                'hijas' => $this->armarArbol($ubicaciones, $u->id, $creadores),
            ])
            ->values()
            ->all();
    }

    private function calcularProfundidad(?int $padreId): int
    {
        return $padreId ? ((int) Ubicacion::whereKey($padreId)->value('profundidad') + 1) : 0;
    }

    /**
     * Trazabilidad legible: código de la sucursal + código de cada ubicación
     * padre (de la raíz hacia abajo) + el propio, p. ej.
     * "CSAN-01/URGE-02/SACH-01" (observaciones generales del cliente §10-11).
     */
    private function calcularRuta(Ubicacion $ubicacion): string
    {
        $idsRaizAHijo = [...array_reverse($ubicacion->idsAncestros()), $ubicacion->id];
        $codigos = Ubicacion::whereIn('id', $idsRaizAHijo)->pluck('codigo', 'id');
        $sucursalCodigo = Sucursal::whereKey($ubicacion->sucursal_id)->value('codigo');

        return collect([$sucursalCodigo])
            ->concat(collect($idsRaizAHijo)->map(fn ($id) => $codigos[$id] ?? null))
            ->filter()
            ->implode('/');
    }
}
