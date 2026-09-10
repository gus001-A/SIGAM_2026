<?php

namespace App\Http\Requests\Mantenimiento;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarSolicitudRequest extends FormRequest
{
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
            'equipo_id' => ['required', 'integer', Rule::exists('equipos', 'id')],
            'descripcion' => ['required', 'string', 'max:2000'],
            'prioridad_id' => ['nullable', 'integer', Rule::exists('prioridades', 'id')],
            'fecha_requerida' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
