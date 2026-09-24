<?php

namespace App\Http\Requests\Inventario;

use App\Models\Sucursal;
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

    /** La sucursal se guarda en MAYÚSCULAS; normaliza antes de validar `unique:codigo`. */
    protected function prepareForValidation(): void
    {
        $this->merge(Sucursal::normalizarMayusculas($this->all()));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('sucursal')?->id;

        return [
            // Si se deja en blanco, el controlador genera "4 letras + consecutivo" del nombre.
            'codigo' => ['nullable', 'string', 'max:40', Rule::unique('sucursales', 'codigo')->ignore($id)],
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
