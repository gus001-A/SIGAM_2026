<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use App\Models\MaterialMantenimiento;
use App\Support\CicloMantenimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Materiales / refacciones utilizadas en una orden. Especificación v2.0 §5.12.
 */
class MaterialMantenimientoController extends Controller
{
    public function store(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->authorize('mantenimientos.editar');

        if (! CicloMantenimiento::permiteGestionEjecucion($mantenimiento->estado?->clave ?? '')) {
            return back()->with('error', 'No se puede agregar materiales: la orden ya fue marcada como realizada.');
        }

        $datos = $request->validate([
            'material_id' => ['nullable', 'integer', Rule::exists('materiales', 'id')],
            'descripcion' => ['nullable', 'required_without:material_id', 'string', 'max:255'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'unidad' => ['required', 'string', 'max:30'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string', 'max:255'],
        ]);

        $mantenimiento->materiales()->create($datos);

        return back()->with('exito', 'Material agregado.');
    }

    public function destroy(Mantenimiento $mantenimiento, MaterialMantenimiento $material): RedirectResponse
    {
        $this->authorize('mantenimientos.editar');

        abort_unless($material->mantenimiento_id === $mantenimiento->id, 404);

        if (! CicloMantenimiento::permiteGestionEjecucion($mantenimiento->estado?->clave ?? '')) {
            return back()->with('error', 'No se puede quitar materiales: la orden ya fue marcada como realizada.');
        }

        $material->delete();

        return back()->with('exito', 'Material eliminado.');
    }
}
