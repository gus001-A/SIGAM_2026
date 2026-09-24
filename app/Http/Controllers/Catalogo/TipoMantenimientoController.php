<?php

namespace App\Http\Controllers\Catalogo;

use App\Models\TipoMantenimiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TipoMantenimientoController extends CatalogoController
{
    protected string $modelo = TipoMantenimiento::class;

    protected string $vista = 'Catalogos/TiposMantenimiento';

    protected string $titulo = 'Tipos de mantenimiento';

    protected bool $generaClave = true;

    protected array $buscables = ['nombre', 'descripcion'];

    protected array $filtrosExactos = ['categoria'];

    protected function consulta(): Builder
    {
        return TipoMantenimiento::query()->withCount(['planes', 'mantenimientos']);
    }

    protected function reglas(Request $request, ?Model $registro = null): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('tipos_mantenimiento', 'nombre')->ignore($registro?->id)],
            'categoria' => ['required', Rule::in(['preventivo', 'correctivo', 'urgente', 'inspeccion'])],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
        ];
    }
}
