<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\CategoriaTarea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaTareaController extends CatalogoController
{
    protected string $modelo = CategoriaTarea::class;

    protected string $vista = 'Catalogos/CategoriasTarea';

    protected string $titulo = 'Categorías de tarea';

    protected bool $generaClave = true;

    protected array $buscables = ['nombre', 'descripcion'];

    protected function consulta(): Builder
    {
        return CategoriaTarea::query()->withCount('tareas');
    }

    protected function reglas(Request $request, ?Model $registro = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('categorias_tarea', 'nombre')->ignore($registro?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
