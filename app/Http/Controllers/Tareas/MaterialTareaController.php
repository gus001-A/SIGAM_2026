<?php

namespace App\Http\Controllers\Tareas;

use App\Http\Controllers\Controller;
use App\Models\MaterialTarea;
use App\Models\Tarea;
use App\Support\CicloTarea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Materiales / insumos usados en una tarea. Espejo de MaterialMantenimientoController.
 * El costo de la tarea ya no se captura a mano: se recalcula aquí.
 */
class MaterialTareaController extends Controller
{
    public function store(Request $request, Tarea $tarea): RedirectResponse
    {
        $this->authorize('tareas.editar');

        if (CicloTarea::esFinal($tarea->estado)) {
            return back()->with('error', 'No se pueden agregar materiales: la tarea ya está realizada o cancelada.');
        }

        $datos = $request->validate([
            'material_id' => ['nullable', 'integer', Rule::exists('materiales', 'id')],
            'descripcion' => ['nullable', 'required_without:material_id', 'string', 'max:255'],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'unidad' => ['required', 'string', 'max:30'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'notas' => ['nullable', 'string', 'max:255'],
        ]);

        $tarea->materiales()->create($datos);
        $tarea->recalcularCosto();

        return back()->with('exito', 'Material agregado.');
    }

    public function destroy(Tarea $tarea, MaterialTarea $material): RedirectResponse
    {
        $this->authorize('tareas.editar');

        abort_unless($material->tarea_id === $tarea->id, 404);

        if (CicloTarea::esFinal($tarea->estado)) {
            return back()->with('error', 'No se pueden quitar materiales: la tarea ya está realizada o cancelada.');
        }

        $material->delete();
        $tarea->recalcularCosto();

        return back()->with('exito', 'Material eliminado.');
    }
}
