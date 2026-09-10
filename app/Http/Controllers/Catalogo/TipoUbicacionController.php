<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\TipoUbicacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoUbicacionController extends CatalogoController
{
    protected string $modelo = TipoUbicacion::class;

    protected string $vista = 'Catalogos/TiposUbicacion';

    protected string $titulo = 'Tipos de ubicación';

    protected bool $generaClave = true;

    protected function consulta(): Builder
    {
        return TipoUbicacion::query()->withCount('ubicaciones');
    }

    protected function reglas(Request $request, ?Model $registro = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tipos_ubicacion', 'nombre')->ignore($registro?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
