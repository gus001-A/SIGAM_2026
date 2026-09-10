<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarSucursalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            $this->routeIs('sucursales.store') ? 'sucursales.crear' : 'sucursales.editar',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('sucursal')?->id;

        return [
            'codigo' => ['required', 'string', 'max:40', Rule::unique('sucursales', 'codigo')->ignore($id)],
            'nombre' => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'digits:10'],
            'correo' => ['nullable', 'email', 'max:255'],
            'responsable_id' => ['nullable', 'integer', Rule::exists('usuarios', 'id')],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
            'notas' => ['nullable', 'string'],
        ];
    }
}
