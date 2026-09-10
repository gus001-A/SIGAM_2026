<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\Marca;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarcaController extends CatalogoController
{
    protected string $modelo = Marca::class;

    protected string $vista = 'Catalogos/Marcas';

    protected string $titulo = 'Marcas';

    protected function consulta(): Builder
    {
        return Marca::query()->withCount('equipos');
    }

    protected function reglas(Request $request, ?Model $registro = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('marcas', 'nombre')->ignore($registro?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
