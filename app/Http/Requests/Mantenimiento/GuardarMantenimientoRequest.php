<?php

namespace App\Http\Requests\Mantenimiento;

use App\Http\Requests\Concerns\ValidaObjetivoMantenimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarMantenimientoRequest extends FormRequest
{
    use ValidaObjetivoMantenimiento;

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
            ...$this->reglasObjetivoMantenimiento(),
            'tipo_id' => ['required', 'integer', Rule::exists('tipos_mantenimiento', 'id')],
            'prioridad_id' => ['required', 'integer', Rule::exists('prioridades', 'id')],
            'plan_id' => ['nullable', 'integer', Rule::exists('planes_mantenimiento', 'id')],
            'programado_inicio' => ['nullable', 'date', 'after_or_equal:today'],
            'programado_fin' => ['nullable', 'date', 'after:programado_inicio'],
            'problema_reportado' => ['nullable', 'string', 'max:2000'],
            'normas' => ['array'],
            'normas.*' => ['integer', Rule::exists('normas', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'programado_inicio.after_or_equal' => 'La fecha programada no puede ser anterior a hoy.',
            'programado_fin.after' => 'La fecha de fin debe ser posterior a la de inicio.',
        ];
    }
}
