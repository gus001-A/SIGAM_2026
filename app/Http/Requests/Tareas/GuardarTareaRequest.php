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
            'clasificacion' => ['nullable', Rule::in(['general', 'proyecto', 'categoria'])],
            'proyecto_id' => ['nullable', 'required_if:clasificacion,proyecto', 'integer', Rule::exists('proyectos', 'id')],
            'categoria_tarea_id' => ['nullable', 'required_if:clasificacion,categoria', 'integer', Rule::exists('categorias_tarea', 'id')],
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
            'proyecto_id.required_if' => 'Selecciona a qué proyecto pertenece la tarea.',
            'categoria_tarea_id.required_if' => 'Selecciona la categoría de la tarea.',
        ];
    }

    /**
     * Datos de la tarea ya validados, forzando a null la referencia que no
     * corresponde a la clasificación elegida (solo una aplica a la vez).
     *
     * @return array<string, mixed>
     */
    public function datosClasificados(): array
    {
        $datos = $this->safe()->only(['titulo', 'descripcion', 'fecha_limite', 'prioridad_id', 'clasificacion', 'proyecto_id', 'categoria_tarea_id']);

        $datos['clasificacion'] ??= 'general';
        $datos['proyecto_id'] = $datos['clasificacion'] === 'proyecto' ? $datos['proyecto_id'] : null;
        $datos['categoria_tarea_id'] = $datos['clasificacion'] === 'categoria' ? $datos['categoria_tarea_id'] : null;

        return $datos;
    }
}
