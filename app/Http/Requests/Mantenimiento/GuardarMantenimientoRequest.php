<?php

namespace App\Http\Requests\Mantenimiento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarMantenimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            $this->routeIs('mantenimientos.store') ? 'mantenimientos.crear' : 'mantenimientos.editar',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'equipo_id' => ['required', 'integer', Rule::exists('equipos', 'id')],
            'tipo_id' => ['required', 'integer', Rule::exists('tipos_mantenimiento', 'id')],
            'prioridad_id' => ['required', 'integer', Rule::exists('prioridades', 'id')],
            'plan_id' => ['nullable', 'integer', Rule::exists('planes_mantenimiento', 'id')],
            'programado_inicio' => ['nullable', 'date'],
            'programado_fin' => ['nullable', 'date', 'after_or_equal:programado_inicio'],
            'problema_reportado' => ['nullable', 'string', 'max:2000'],
            'normas' => ['array'],
            'normas.*' => ['integer', Rule::exists('normas', 'id')],
        ];
    }
}
