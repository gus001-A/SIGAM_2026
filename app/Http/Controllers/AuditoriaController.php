<?php

namespace App\Http\Controllers;

use App\Models\RegistroAuditoria;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Support\ExportadorCsv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bitácora de auditoría (solo lectura). Especificación v2.0 §5.18 / §13.
 * El registro es inmutable: no hay crear/editar/borrar.
 */
class AuditoriaController extends Controller
{
    /** Registros por página del listado. */
    private const POR_PAGINA = 15;

    /** Columnas por las que se permite ordenar. */
    private const ORDENABLES = ['created_at', 'modulo', 'accion'];

    public function index(Request $request): Response
    {
        $this->authorize('auditoria.ver');

        $orden = in_array($request->query('orden'), self::ORDENABLES, true) ? $request->query('orden') : 'created_at';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $registros = $this->filtrar($request)
            ->with(['usuario:id,nombre,apellidos', 'sucursal:id,nombre'])
            ->orderBy($orden, $dir)
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (RegistroAuditoria $r) => [
                'id' => $r->id,
                'usuario' => $r->usuario?->nombre_completo,
                'accion' => $r->accion,
                'modulo' => $r->modulo,
                'sucursal' => $r->sucursal?->nombre,
                'entidad' => $r->auditable_type ? class_basename($r->auditable_type).' #'.$r->auditable_id : null,
                'ip' => $r->ip,
                'navegador' => $r->navegador,
                'valores_anteriores' => $r->valores_anteriores,
                'valores_nuevos' => $r->valores_nuevos,
                'metadatos' => $r->metadatos,
                'created_at' => $r->created_at,
            ]);

        return Inertia::render('Auditoria/Index', [
            'registros' => $registros,
            'filtros' => $request->only(['usuario_id', 'sucursal_id', 'modulo', 'accion', 'ip', 'registro', 'desde', 'hasta']),
            'orden' => ['campo' => $orden, 'dir' => $dir],
            'catalogos' => [
                'usuarios' => Usuario::withTrashed()->orderBy('nombre')->get(['id', 'nombre', 'apellidos'])
                    ->map(fn (Usuario $u) => ['id' => $u->id, 'nombre' => $u->nombre_completo]),
                'sucursales' => Sucursal::orderBy('nombre')->get(['id', 'nombre']),
                'modulos' => RegistroAuditoria::query()->distinct()->orderBy('modulo')->pluck('modulo'),
                'acciones' => RegistroAuditoria::query()->distinct()->orderBy('accion')->pluck('accion'),
            ],
        ]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $this->authorize('auditoria.exportar');

        $registros = $this->filtrar($request)
            ->with(['usuario:id,nombre,apellidos', 'sucursal:id,nombre'])
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get();

        $nombre = 'auditoria-'.now()->format('Ymd-His').'.csv';

        $filas = $registros->map(fn (RegistroAuditoria $r) => [
            optional($r->created_at)->format('Y-m-d H:i:s'),
            $r->usuario?->nombre_completo,
            $r->accion,
            $r->modulo,
            $r->sucursal?->nombre,
            $r->auditable_type ? class_basename($r->auditable_type).' #'.$r->auditable_id : null,
            $r->ip,
        ]);

        return ExportadorCsv::descargar(
            $nombre,
            ['Fecha', 'Usuario', 'Acción', 'Módulo', 'Sucursal', 'Entidad', 'IP'],
            $filas,
        );
    }

    private function filtrar(Request $request): Builder
    {
        return RegistroAuditoria::query()
            ->when($request->integer('usuario_id'), fn (Builder $q, $v) => $q->where('usuario_id', $v))
            ->when($request->integer('sucursal_id'), fn (Builder $q, $v) => $q->where('sucursal_id', $v))
            ->when($request->filled('modulo'), fn (Builder $q) => $q->where('modulo', $request->query('modulo')))
            ->when($request->filled('accion'), fn (Builder $q) => $q->where('accion', $request->query('accion')))
            ->when($request->filled('ip'), fn (Builder $q) => $q->where('ip', 'like', '%'.trim((string) $request->query('ip')).'%'))
            ->when($request->filled('registro'), fn (Builder $q) => $q->where('auditable_id', $request->integer('registro')))
            ->when($request->filled('desde'), fn (Builder $q) => $q->where('created_at', '>=', $request->date('desde')))
            ->when($request->filled('hasta'), fn (Builder $q) => $q->where('created_at', '<=', $request->date('hasta')?->endOfDay()));
    }
}
