<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\EstadoMantenimiento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstadoMantenimientoController extends CatalogoController
{
    protected string $modelo = EstadoMantenimiento::class;

    protected string $vista = 'Catalogos/EstadosMantenimiento';

    protected string $titulo = 'Estados de mantenimiento';

    protected bool $generaClave = true;

    protected array $ordenablesExtra = ['orden'];

    protected array $buscables = ['nombre', 'descripcion'];

    protected array $filtrosExactos = ['es_abierto', 'es_terminal'];

    protected function reglas(Request $request, ?Model $registro = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('estados_mantenimiento', 'nombre')->ignore($registro?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'orden' => ['required', 'integer', 'min:0'],
            'es_terminal' => ['boolean'],
            'es_abierto' => ['boolean'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
