<?php

namespace App\Http\Requests\Tareas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarTareaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            $this->routeIs('tareas.store') ? 'tareas.crear' : 'tareas.editar',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $reglas = [
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string', 'max:2000'],
            'fecha_limite' => ['required', 'date'],
            'prioridad_id' => ['nullable', 'integer', Rule::exists('prioridades', 'id')],
        ];

        if ($this->routeIs('tareas.store')) {
            // Al crear no tiene sentido nacer ya vencida; al editar sí se permite
            // conservar la fecha original de una tarea que ya venció.
            $reglas['fecha_limite'][] = 'after_or_equal:today';
            $reglas['responsables'] = ['required', 'array', 'min:1'];
            $reglas['responsables.*'] = ['integer', Rule::exists('usuarios', 'id')];
        }

        return $reglas;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fecha_limite.after_or_equal' => 'La fecha límite no puede ser anterior a hoy.',
        ];
    }
}
