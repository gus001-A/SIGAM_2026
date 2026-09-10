<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\Prioridad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PrioridadController extends CatalogoController
{
    protected string $modelo = Prioridad::class;

    protected string $vista = 'Catalogos/Prioridades';

    protected string $titulo = 'Prioridades';

    protected bool $generaClave = true;

    protected array $ordenablesExtra = ['nivel', 'minutos_respuesta'];

    protected function reglas(Request $request, ?Model $registro = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('prioridades', 'nombre')->ignore($registro?->id)],
            'nivel' => ['required', 'integer', 'min:1', 'max:255'],
            'minutos_respuesta' => ['nullable', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'max:20'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
