<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\TipoArea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoAreaController extends CatalogoController
{
    protected string $modelo = TipoArea::class;

    protected string $vista = 'Catalogos/TiposArea';

    protected string $titulo = 'Tipos de área';

    protected bool $generaClave = true;

    protected array $buscables = ['nombre', 'descripcion'];

    protected function consulta(): Builder
    {
        return TipoArea::query()->withCount('ubicaciones');
    }

    protected function reglas(Request $request, ?Model $registro = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tipos_area', 'nombre')->ignore($registro?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'dias_limpieza' => ['required', 'integer', 'min:1', 'max:365'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
