<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class GuardarUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(
            $this->routeIs('usuarios.store') ? 'usuarios.crear' : 'usuarios.editar',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('usuario')?->id;
        $creando = $this->routeIs('usuarios.store');

        return [
            'nombre' => ['required', 'string', 'max:255'],
            'apellidos' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('usuarios', 'email')->ignore($id)],
            'telefono' => ['nullable', 'digits:10'],
            'sucursal_id' => ['nullable', 'integer', Rule::exists('sucursales', 'id')],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
            'password' => [$creando ? 'required' : 'nullable', 'confirmed', Password::defaults()],
            // Un usuario tiene exactamente un rol.
            'roles' => ['required', 'array', 'size:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
            'especialidades_equipo' => ['nullable', 'array'],
            'especialidades_equipo.*' => ['integer', Rule::exists('tipos_equipo', 'id')],
            'especialidades_mantenimiento' => ['nullable', 'array'],
            'especialidades_mantenimiento.*' => ['integer', Rule::exists('tipos_mantenimiento', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'Selecciona el rol del usuario.',
            'roles.size' => 'El usuario debe tener exactamente un rol.',
        ];
    }
}
