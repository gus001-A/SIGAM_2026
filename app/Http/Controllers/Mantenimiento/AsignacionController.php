<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use App\Models\AsignacionMantenimiento;
use App\Models\Mantenimiento;
use App\Models\Usuario;
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

        if (! CicloMantenimiento::permiteGestionEjecucion($mantenimiento->estado?->clave ?? '')) {
            return back()->with('error', 'No se puede asignar un técnico: la orden ya fue marcada como realizada.');
        }

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

        $tecnico = Usuario::findOrFail($datos['tecnico_id']);

        DB::transaction(function () use ($mantenimiento, $datos, $tecnico, $request): void {
            // Si se marca como principal, desmarcar cualquier otro principal activo.
            if ($datos['es_principal'] ?? false) {
                $mantenimiento->asignaciones()->whereNull('desasignado_at')->update(['es_principal' => false]);
            }

            $mantenimiento->asignaciones()->create([
                'tecnico_id' => $tecnico->id,
                'asignado_por' => $request->user()->id,
                'es_principal' => $datos['es_principal'] ?? false,
                'notas' => $datos['notas'] ?? null,
                'asignado_at' => now(),
            ]);

            // 👇 Registro en la bitácora (esto faltaba y por eso no aparecía nada)
            $mantenimiento->observaciones()->create([
                'tipo' => 'comentario',
                'cuerpo' => "Se asignó al técnico {$tecnico->nombre}"
                    .(! empty($datos['es_principal']) ? ' como responsable principal.' : '.'),
                'usuario_id' => $request->user()->id,
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

        return back()->with('exito', "Técnico {$tecnico->nombre} asignado.");
    }

    public function destroy(Request $request, Mantenimiento $mantenimiento, AsignacionMantenimiento $asignacion): RedirectResponse
    {
        $this->authorize('mantenimientos.asignar');

        abort_unless((int) $asignacion->mantenimiento_id === (int) $mantenimiento->id, 404);

        if ($asignacion->desasignado_at) {
            return back()->with('error', 'Esta asignación ya había sido retirada.');
        }

        if (! CicloMantenimiento::permiteGestionEjecucion($mantenimiento->estado?->clave ?? '')) {
            return back()->with('error', 'No se puede retirar un técnico: la orden ya fue marcada como realizada.');
        }

        $tecnicoNombre = $asignacion->tecnico?->nombre ?? 'técnico';

        DB::transaction(function () use ($mantenimiento, $asignacion, $request, $tecnicoNombre): void {
            $asignacion->update(['desasignado_at' => now()]);

            // 👇 Registro en la bitácora también al retirar
            $mantenimiento->observaciones()->create([
                'tipo' => 'comentario',
                'cuerpo' => "Se retiró al técnico {$tecnicoNombre}.",
                'usuario_id' => $request->user()->id,
            ]);
        });

        return back()->with('exito', 'Técnico retirado de la orden.');
    }
}
