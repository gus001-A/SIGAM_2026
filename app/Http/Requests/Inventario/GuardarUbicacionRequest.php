<?php

namespace App\Http\Requests\Inventario;

use App\Models\Ubicacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarUbicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            $this->routeIs('ubicaciones.store') ? 'ubicaciones.crear' : 'ubicaciones.editar',
        );
    }

    /** La ubicación se guarda en MAYÚSCULAS; normaliza antes de validar `unique:codigo`. */
    protected function prepareForValidation(): void
    {
        $this->merge(Ubicacion::normalizarMayusculas($this->all()));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ubicacion = $this->route('ubicacion');

        return [
            'sucursal_id' => ['required', 'integer', Rule::exists('sucursales', 'id')],
            'padre_id' => [
                'nullable', 'integer',
                Rule::exists('ubicaciones', 'id')->where('sucursal_id', $this->input('sucursal_id')),
            ],
            'tipo_id' => ['nullable', 'integer', Rule::exists('tipos_ubicacion', 'id')],
            'tipo_area_id' => ['nullable', 'integer', Rule::exists('tipos_area', 'id')],
            'tipo_limpieza_id' => ['nullable', 'integer', Rule::exists('tipos_limpieza', 'id')],
            // Si se deja en blanco, el controlador genera "4 letras + consecutivo" del nombre.
            'codigo' => [
                'nullable', 'string', 'max:60',
                Rule::unique('ubicaciones', 'codigo')
                    ->where('sucursal_id', $this->input('sucursal_id'))
                    ->ignore($ubicacion?->id),
            ],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $ubicacion = $this->route('ubicacion');
            $padreId = $this->input('padre_id');

            if (! $ubicacion || ! $padreId) {
                return;
            }

            // RF-024: no permitir ciclos en el árbol.
            if ((int) $padreId === $ubicacion->id) {
                $v->errors()->add('padre_id', 'Una ubicación no puede depender de sí misma.');

                return;
            }

            $padre = Ubicacion::find($padreId);
            if ($padre && in_array($ubicacion->id, $padre->idsAncestros(), true)) {
                $v->errors()->add('padre_id', 'La ubicación padre no puede ser una de sus descendientes.');
            }
        });
    }
}
