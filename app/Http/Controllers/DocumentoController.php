<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Norma;
use App\Models\Proveedor;
use App\Models\SolicitudMantenimiento;
use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gestión documental polimórfica. Especificación v2.0 §14.
 * Los archivos privados se sirven a través de una ruta autorizada de Laravel.
 */
class DocumentoController extends Controller
{
    /** Entidades a las que se pueden adjuntar documentos. */
    private const RELACIONABLES = [
        'equipo' => Equipo::class,
        'sucursal' => Sucursal::class,
        'proveedor' => Proveedor::class,
        'norma' => Norma::class,
        'mantenimiento' => Mantenimiento::class,
        'solicitud' => SolicitudMantenimiento::class,
    ];

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('documentos.crear');

        $datos = $request->validate([
            'archivo' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
            'relacionable_tipo' => ['required', Rule::in(array_keys(self::RELACIONABLES))],
            'relacionable_id' => ['required', 'integer'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:60'],
            'rol' => ['nullable', 'string', 'max:40'],
            'visibilidad' => ['required', Rule::in(['privado', 'publico'])],
            'vence_at' => ['nullable', 'date'],
        ]);

        $modelo = self::RELACIONABLES[$datos['relacionable_tipo']];
        $entidad = $modelo::findOrFail($datos['relacionable_id']);

        $archivo = $request->file('archivo');
        $disco = $datos['visibilidad'] === 'publico' ? 'public' : 'local';
        $ruta = $archivo->store('documentos/'.now()->format('Y/m'), $disco);

        $documento = Documento::create([
            'disco' => $disco,
            'ruta' => $ruta,
            'nombre_original' => $archivo->getClientOriginalName(),
            'titulo' => $datos['titulo'] ?? null,
            'categoria' => $datos['categoria'] ?? null,
            'tipo_mime' => $archivo->getClientMimeType(),
            'tamano' => $archivo->getSize(),
            'checksum' => hash_file('sha256', $archivo->getRealPath()),
            'visibilidad' => $datos['visibilidad'],
            'vence_at' => $datos['vence_at'] ?? null,
            'subido_por' => $request->user()->id,
        ]);

        $entidad->documentos()->attach($documento->id, ['rol' => $datos['rol'] ?? null]);

        return back()->with('exito', 'Documento cargado.');
    }

    public function download(Request $request, Documento $documento): StreamedResponse
    {
        abort_unless(
            $documento->esPublico() || $request->user()?->can('documentos.ver'),
            403,
        );

        abort_unless(Storage::disk($documento->disco)->exists($documento->ruta), 404);

        return Storage::disk($documento->disco)->download(
            $documento->ruta,
            $documento->nombre_original,
        );
    }

    /** Sirve el archivo en línea (para previsualizar imágenes / PDF). */
    public function previsualizar(Request $request, Documento $documento): StreamedResponse
    {
        abort_unless(
            $documento->esPublico() || $request->user()?->can('documentos.ver'),
            403,
        );

        abort_unless(Storage::disk($documento->disco)->exists($documento->ruta), 404);

        return Storage::disk($documento->disco)->response(
            $documento->ruta,
            $documento->nombre_original,
            ['Content-Type' => $documento->tipo_mime ?: 'application/octet-stream'],
        );
    }

    public function destroy(Documento $documento): RedirectResponse
    {
        $this->authorize('documentos.desactivar');

        // Baja lógica: se conserva el archivo y los metadatos (§13/§14).
        $documento->delete();

        return back()->with('exito', 'Documento eliminado.');
    }
}
