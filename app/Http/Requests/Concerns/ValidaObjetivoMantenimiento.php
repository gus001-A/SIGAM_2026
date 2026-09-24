<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Una solicitud/orden de mantenimiento apunta a un equipo O a una instalación
 * (ubicación), nunca a ambos ni a ninguno. Reglas compartidas por
 * GuardarSolicitudRequest y GuardarMantenimientoRequest (Planes valida esto
 * mismo de forma inline en PlanMantenimientoController::validar()).
 */
trait ValidaObjetivoMantenimiento
{
    protected function reglasObjetivoMantenimiento(): array
    {
        return [
            'equipo_id' => ['nullable', 'integer', Rule::exists('equipos', 'id')],
            'ubicacion_id' => ['nullable', 'integer', Rule::exists('ubicaciones', 'id')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $tieneEquipo = filled($this->input('equipo_id'));
            $tieneUbicacion = filled($this->input('ubicacion_id'));
            if ($tieneEquipo === $tieneUbicacion) {
                $v->errors()->add('equipo_id', 'Selecciona un equipo o una instalación, no ambos ni ninguno.');
            }
        });
    }
}
