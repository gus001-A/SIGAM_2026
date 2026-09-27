<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\Material;
use App\Support\Folios;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaterialController extends CatalogoController
{
    protected string $modelo = Material::class;

    protected string $vista = 'Catalogos/Materiales';

    protected string $titulo = 'Materiales y refacciones';

    protected array $buscables = ['nombre', 'codigo', 'unidad', 'costo_referencia'];

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

    protected function completarDatos(array $datos, ?Model $registro = null): array
    {
        if (empty($datos['codigo'])) {
            $datos['codigo'] = Folios::codigoMaterial($datos['nombre'] ?? '', $registro?->id);
        }

        return $datos;
    }
}
