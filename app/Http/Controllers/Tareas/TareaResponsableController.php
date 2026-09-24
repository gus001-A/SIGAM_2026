<?php

namespace App\Http\Controllers\Tareas;

use App\Http\Controllers\Controller;
use App\Models\Tarea;
use App\Models\TareaResponsable;
use App\Support\CicloTarea;
use App\Support\Notificaciones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Alta/baja de responsables de una tarea ya creada. Espejo de
 * `AsignacionController` (técnicos de una orden de mantenimiento).
 */
class TareaResponsableController extends Controller
{
    public function store(Request $request, Tarea $tarea): RedirectResponse
    {
        $this->authorize('tareas.asignar');

        $datos = $request->validate([
            'usuario_id' => [
                'required', 'integer', Rule::exists('usuarios', 'id'),
                Rule::unique('tarea_responsables', 'usuario_id')
                    ->where('tarea_id', $tarea->id)
                    ->whereNull('desasignado_at'),
            ],
            'es_principal' => ['boolean'],
            'notas' => ['nullable', 'string', 'max:255'],
        ]);

        if ($datos['es_principal'] ?? false) {
            $tarea->asignaciones()->whereNull('desasignado_at')->update(['es_principal' => false]);
        }

        $tarea->asignaciones()->create([
            'usuario_id' => $datos['usuario_id'],
            'asignado_por' => $request->user()->id,
            'es_principal' => $datos['es_principal'] ?? false,
            'notas' => $datos['notas'] ?? null,
            'asignado_at' => now(),
        ]);

        if ($datos['usuario_id'] !== $request->user()->id) {
            Notificaciones::crear(
                $datos['usuario_id'],
                'tarea_asignada',
                "Nueva tarea: {$tarea->titulo}",
                'Se te asignó una tarea con fecha límite '.$tarea->fecha_limite->format('d/m/Y').'.',
                ['ref' => "tarea:{$tarea->id}", 'url' => route('tareas.show', $tarea->id)],
            );
        }

        return back()->with('exito', 'Responsable asignado.');
    }

    public function destroy(Tarea $tarea, TareaResponsable $responsable): RedirectResponse
    {
        $this->authorize('tareas.asignar');

        abort_unless($responsable->tarea_id === $tarea->id, 404);

        if (CicloTarea::esFinal($tarea->estado)) {
            return back()->with('error', 'La tarea ya está cerrada.');
        }

        $activos = $tarea->asignaciones()->whereNull('desasignado_at')->count();
        if ($activos <= 1) {
            return back()->with('error', 'La tarea debe conservar al menos un responsable.');
        }

        $responsable->update(['desasignado_at' => now()]);

        return back()->with('exito', 'Responsable retirado de la tarea.');
    }
}
