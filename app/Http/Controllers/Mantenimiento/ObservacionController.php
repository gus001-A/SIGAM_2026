<?php

namespace App\Http\Controllers\Mantenimiento;

use App\Http\Controllers\Controller;
use App\Models\Mantenimiento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Observaciones / bitácora de una orden. Especificación v2.0 §5.12.
 */
class ObservacionController extends Controller
{
    public function store(Request $request, Mantenimiento $mantenimiento): RedirectResponse
    {
        $this->authorize('mantenimientos.ver');

        $datos = $request->validate([
            'tipo' => ['required', Rule::in(['comentario', 'diagnostico', 'actividad', 'supervision'])],
            'cuerpo' => ['required', 'string', 'max:2000'],
        ]);

        $mantenimiento->observaciones()->create([
            'usuario_id' => $request->user()->id,
            'tipo' => $datos['tipo'],
            'cuerpo' => $datos['cuerpo'],
            'created_at' => now(),
        ]);

        return back()->with('exito', 'Observación registrada.');
    }
}
