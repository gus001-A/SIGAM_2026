<?php

namespace App\Http\Requests\Inventario;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardarEquipoRequest extends FormRequest
{
    /** Texto libre: acepta ñ, acentos y signos de puntuación; bloquea < > { } \. */
    private const RE_TEXTO = '/^[^<>{}\\\\]+$/u';

    /** Código / serie / factura: letras, dígitos, espacios y . - _ / #. */
    private const RE_CODIGO = '/^[\p{L}\p{N}\s._\/\-#]+$/u';

    public function authorize(): bool
    {
        return $this->user()->can(
            $this->routeIs('equipos.store') ? 'equipos.crear' : 'equipos.editar',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $id = $this->route('equipo')?->id;

        return [
            'codigo_activo' => ['required', 'string', 'max:80', 'regex:'.self::RE_CODIGO, Rule::unique('equipos', 'codigo_activo')->ignore($id)],
            'codigo_barras' => ['nullable', 'string', 'max:120', 'regex:'.self::RE_CODIGO],
            'descripcion' => ['required', 'string', 'max:255', 'regex:'.self::RE_TEXTO],
            'tipo_id' => ['nullable', 'integer', Rule::exists('tipos_equipo', 'id')],
            'marca_id' => ['nullable', 'integer', Rule::exists('marcas', 'id')],
            'modelo' => ['nullable', 'string', 'max:255', 'regex:'.self::RE_CODIGO],
            'numero_serie' => ['nullable', 'string', 'max:255', 'regex:'.self::RE_CODIGO],
            'sucursal_id' => ['required', 'integer', Rule::exists('sucursales', 'id')],
            'ubicacion_id' => [
                'nullable', 'integer',
                Rule::exists('ubicaciones', 'id')->where('sucursal_id', $this->input('sucursal_id')),
            ],
            'proveedor_id' => ['nullable', 'integer', Rule::exists('proveedores', 'id')],
            'responsable_id' => ['nullable', 'integer', Rule::exists('usuarios', 'id')],
            'estado_id' => ['nullable', 'integer', Rule::exists('estados_equipo', 'id')],
            'fecha_adquisicion' => ['nullable', 'date', 'before_or_equal:today'],
            'numero_factura' => ['nullable', 'string', 'max:255', 'regex:'.self::RE_CODIGO],
            'valor_adquisicion' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'garantia_hasta' => ['nullable', 'date', 'after_or_equal:fecha_adquisicion'],
            'especificaciones' => ['nullable', 'array'],
            'vida_util' => ['nullable', 'string', 'max:255', 'regex:'.self::RE_TEXTO],
            'notas' => ['nullable', 'string', 'max:2000'],
            'normas' => ['array'],
            'normas.*' => ['integer', Rule::exists('normas', 'id')],
            'motivo_cambio_ubicacion' => ['nullable', 'string', 'max:255', 'regex:'.self::RE_TEXTO],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'codigo_activo.regex' => 'El código solo admite letras, números y . - _ / #.',
            'codigo_barras.regex' => 'El código de barras solo admite letras, números y . - _ / #.',
            'descripcion.regex' => 'La descripción no puede contener < > { } \\.',
            'modelo.regex' => 'El modelo solo admite letras, números y . - _ / #.',
            'numero_serie.regex' => 'El número de serie solo admite letras, números y . - _ / #.',
            'numero_factura.regex' => 'El número de factura solo admite letras, números y . - _ / #.',
            'vida_util.regex' => 'La vida útil no puede contener < > { } \\.',
            'valor_adquisicion.numeric' => 'El valor de adquisición debe ser un número (solo dígitos).',
            'valor_adquisicion.min' => 'El valor de adquisición no puede ser negativo.',
            'fecha_adquisicion.before_or_equal' => 'La fecha de adquisición no puede ser futura.',
            'garantia_hasta.after_or_equal' => 'La garantía debe vencer después de la fecha de adquisición.',
        ];
    }
}
