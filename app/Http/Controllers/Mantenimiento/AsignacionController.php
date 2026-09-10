<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use App\Models\AsignacionMantenimiento;
use App\Models\Mantenimiento;
use App\Support\CicloMantenimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Asignación de técnicos a una orden. Especificación v2.0 §5.12 / RF-044.
 */
class AsignacionController extends Controller
{
    public function store(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->authorize('mantenimientos.asignar');

        $datos = $request->validate([
            'tecnico_id' => [
                'required', 'integer', Rule::exists('usuarios', 'id'),
                Rule::unique('asignaciones_mantenimiento', 'tecnico_id')
                    ->where('mantenimiento_id', $mantenimiento->id)
                    ->whereNull('desasignado_at'),
            ],
            'es_principal' => ['boolean'],
            'notas' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($mantenimiento, $datos, $request): void {
            if ($datos['es_principal'] ?? false) {
                $mantenimiento->asignaciones()->whereNull('desasignado_at')->update(['es_principal' => false]);
            }

            $mantenimiento->asignaciones()->create([
                'tecnico_id' => $datos['tecnico_id'],
                'asignado_por' => $request->user()->id,
                'es_principal' => $datos['es_principal'] ?? false,
                'notas' => $datos['notas'] ?? null,
                'asignado_at' => now(),
            ]);

            // autorizado → asignado al colocar el primer técnico.
            if ($mantenimiento->estado->clave === 'autorizado') {
                $asignado = CicloMantenimiento::estado('asignado');
                $mantenimiento->historialEstados()->create([
                    'estado_origen_id' => $mantenimiento->estado_id,
                    'estado_destino_id' => $asignado->id,
                    'cambiado_por' => $request->user()->id,
                    'nota' => 'Técnico asignado',
                    'cambiado_at' => now(),
                ]);
                $mantenimiento->update(['estado_id' => $asignado->id]);
            }
        });

        return back()->with('exito', 'Técnico asignado.');
    }

    public function destroy(Mantenimiento $mantenimiento, AsignacionMantenimiento $asignacion): RedirectResponse
    {
        $this->authorize('mantenimientos.asignar');

        abort_unless($asignacion->mantenimiento_id === $mantenimiento->id, 404);

        $asignacion->update(['desasignado_at' => now()]);

        return back()->with('exito', 'Técnico retirado de la orden.');
    }
}
