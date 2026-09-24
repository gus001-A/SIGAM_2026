<?php

namespace App\Http\Requests\Mantenimiento;

use App\Http\Requests\Concerns\ValidaObjetivoMantenimiento;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarSolicitudRequest extends FormRequest
{
    use ValidaObjetivoMantenimiento;

    public function authorize(): bool
    {
        return $this->user()->can(
            $this->routeIs('solicitudes.store') ? 'solicitudes.crear' : 'solicitudes.editar',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->reglasObjetivoMantenimiento(),
            'descripcion' => ['required', 'string', 'max:2000'],
            'prioridad_id' => ['nullable', 'integer', Rule::exists('prioridades', 'id')],
            'fecha_requerida' => ['nullable', 'date', 'after:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_requerida.after' => 'La fecha requerida debe ser posterior a hoy.',
        ];
    }
}
