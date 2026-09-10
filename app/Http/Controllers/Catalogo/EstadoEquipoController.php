<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\EstadoEquipo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EstadoEquipoController extends CatalogoController
{
    protected string $modelo = EstadoEquipo::class;

    protected string $vista = 'Catalogos/EstadosEquipo';

    protected string $titulo = 'Estados de equipo';

    protected bool $generaClave = true;

    protected function consulta(): Builder
    {
        return EstadoEquipo::query()->withCount('equipos');
    }

    protected function reglas(Request $request, ?Model $registro = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('estados_equipo', 'nombre')->ignore($registro?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'es_operativo' => ['boolean'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
