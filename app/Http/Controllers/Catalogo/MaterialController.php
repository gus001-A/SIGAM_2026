<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\Material;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaterialController extends CatalogoController
{
    protected string $modelo = Material::class;

    protected string $vista = 'Catalogos/Materiales';

    protected string $titulo = 'Materiales y refacciones';

    protected array $buscables = ['nombre', 'codigo'];

    protected function reglas(Request $request, ?Model $registro = null): array
    {
        return [
            'codigo' => ['nullable', 'string', 'max:60', Rule::unique('materiales', 'codigo')->ignore($registro?->id)],
            'nombre' => ['required', 'string', 'max:255'],
            'unidad' => ['required', 'string', 'max:30'],
            'costo_referencia' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
