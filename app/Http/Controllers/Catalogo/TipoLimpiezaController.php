<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\TipoLimpieza;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoLimpiezaController extends CatalogoController
{
    protected string $modelo = TipoLimpieza::class;

    protected string $vista = 'Catalogos/TiposLimpieza';

    protected string $titulo = 'Tipos de limpieza';

    protected bool $generaClave = true;

    protected array $buscables = ['nombre', 'frecuencia', 'descripcion'];

    protected function consulta(): Builder
    {
        return TipoLimpieza::query()->withCount('ubicaciones');
    }

    protected function reglas(Request $request, ?Model $registro = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tipos_limpieza', 'nombre')->ignore($registro?->id)],
            'frecuencia' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
