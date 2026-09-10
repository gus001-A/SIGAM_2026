<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\TipoEquipo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoEquipoController extends CatalogoController
{
    protected string $modelo = TipoEquipo::class;

    protected string $vista = 'Catalogos/TiposEquipo';

    protected string $titulo = 'Tipos de equipo';

    protected bool $generaClave = true;

    protected function consulta(): Builder
    {
        return TipoEquipo::query()->withCount('equipos');
    }

    protected function reglas(Request $request, ?Model $registro = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tipos_equipo', 'nombre')->ignore($registro?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
