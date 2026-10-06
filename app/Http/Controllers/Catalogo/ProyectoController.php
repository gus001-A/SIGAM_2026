<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProyectoController extends CatalogoController
{
    protected string $modelo = Proyecto::class;

    protected string $vista = 'Catalogos/Proyectos';

    protected string $titulo = 'Proyectos';

    protected bool $generaClave = true;

    protected array $buscables = ['nombre', 'descripcion'];

    protected function consulta(): Builder
    {
        return Proyecto::query()->withCount('tareas');
    }

    protected function reglas(Request $request, ?Model $registro = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('proyectos', 'nombre')->ignore($registro?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
