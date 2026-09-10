<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Notificaciones in-app del usuario. Especificación v2.0 §12.
 */
class NotificacionController extends Controller
{
    /** Registros por página del listado. */
    private const POR_PAGINA = 15;

    public function index(Request $request): Response
    {
        $base = $request->user()->notificaciones();

        $notificaciones = (clone $base)
            ->when($request->query('ver') === 'no_leidas', fn (Builder $q) => $q->noLeidas())
            ->when($request->query('ver') === 'leidas', fn (Builder $q) => $q->whereNotNull('leida_at'))
            ->when($request->filled('tipo'), fn (Builder $q) => $q->where('tipo', $request->query('tipo')))
            ->latest('id')
            ->paginate(self::POR_PAGINA)
            ->withQueryString()
            ->through(fn (Notificacion $n) => [
                'id' => $n->id,
                'tipo' => $n->tipo,
                'titulo' => $n->titulo,
                'cuerpo' => $n->cuerpo,
                'url' => $n->datos['url'] ?? null,
                'leida' => $n->leida_at !== null,
                'created_at' => $n->created_at,
            ]);

        return Inertia::render('Notificaciones/Index', [
            'notificaciones' => $notificaciones,
            'filtros' => $request->only(['ver', 'tipo']),
            'noLeidas' => (clone $base)->noLeidas()->count(),
            'tipos' => (clone $base)->distinct()->orderBy('tipo')->pluck('tipo'),
        ]);
    }

    /** Contador + últimas notificaciones para el panel flotante de la campana. */
    public function noLeidas(Request $request): JsonResponse
    {
        $base = $request->user()->notificaciones();

        return response()->json([
            'total' => (clone $base)->noLeidas()->count(),
            'items' => (clone $base)->latest('id')->limit(8)->get()->map(fn (Notificacion $n) => [
                'id' => $n->id,
                'tipo' => $n->tipo,
                'titulo' => $n->titulo,
                'cuerpo' => $n->cuerpo,
                'url' => $n->datos['url'] ?? null,
                'leida' => $n->leida_at !== null,
                'created_at' => $n->created_at,
            ]),
        ]);
    }

    public function marcarLeida(Request $request, Notificacion $notificacion): RedirectResponse
    {
        abort_unless($notificacion->usuario_id === $request->user()->id, 403);

        $notificacion->marcarLeida();

        return back(fallback: route('notificaciones.index'));
    }

    public function marcarTodas(Request $request): RedirectResponse
    {
        $request->user()->notificaciones()->noLeidas()->update(['leida_at' => now()]);

        return back(fallback: route('notificaciones.index'))->with('exito', 'Notificaciones marcadas como leídas.');
    }
}
