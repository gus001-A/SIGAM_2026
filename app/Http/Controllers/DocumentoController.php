<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Norma;
use App\Models\Proveedor;
use App\Models\SolicitudMantenimiento;
use App\Models\Sucursal;
use App\Models\Tarea;
use App\Support\Documentos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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
        'tarea' => Tarea::class,
    ];

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('documentos.crear');

        $datos = $request->validate([
            'archivo' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
            'relacionable_tipo' => ['required', Rule::in(array_keys(self::RELACIONABLES))],
            'relacionable_id' => ['required', 'integer'],
            'titulo' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'rol' => ['nullable', 'string', 'max:40'],
        ]);

        $modelo = self::RELACIONABLES[$datos['relacionable_tipo']];
        $entidad = $modelo::findOrFail($datos['relacionable_id']);

        $archivo = $request->file('archivo');

        // Disco privado: local o la nube, según `DOCUMENTOS_DISK`.
        $documento = Documento::create([
            ...Documentos::guardar($archivo),
            'titulo' => $datos['titulo'] ?? null,
            'descripcion' => $datos['descripcion'] ?? null,
            'subido_por' => $request->user()->id,
        ]);

        $entidad->documentos()->attach($documento->id, ['rol' => $datos['rol'] ?? null]);

        return back()->with('exito', 'Documento cargado.');
    }

    public function download(Request $request, Documento $documento): StreamedResponse
    {
        abort_unless(
            $request->user()?->can('documentos.ver'),
            403,
        );

        return $this->servir($documento, 'attachment');
    }

    /** Sirve el archivo en línea (para previsualizar imágenes / PDF). */
    public function previsualizar(Request $request, Documento $documento): StreamedResponse
    {
        abort_unless(
            $request->user()?->can('documentos.ver'),
            403,
        );

        return $this->servir($documento, 'inline');
    }

    /**
     * Manda el archivo al navegador con UNA sola lectura al disco. En la nube
     * cada consulta cuenta contra el límite diario gratuito, y
     * `Storage::response()` hace hasta cuatro (existe, tipo, tamaño y
     * descarga). El tipo y el tamaño ya están guardados en el documento, y si
     * el archivo no existe la propia lectura falla y se responde 404.
     */
    private function servir(Documento $documento, string $disposicion): StreamedResponse
    {
        try {
            $flujo = Storage::disk($documento->disco)->readStream($documento->ruta);
        } catch (Throwable) {
            $flujo = null;
        }
        abort_unless(is_resource($flujo), 404);

        $respuesta = new StreamedResponse(function () use ($flujo): void {
            fpassthru($flujo);
            fclose($flujo);
        });

        $nombre = $documento->nombre_original ?: basename($documento->ruta);
        $respuesta->headers->set('Content-Type', $documento->tipo_mime ?: 'application/octet-stream');
        $respuesta->headers->set('Content-Disposition', $respuesta->headers->makeDisposition(
            $disposicion,
            $nombre,
            str_replace(['%', '/', '\\'], '', Str::ascii($nombre)) ?: 'archivo',
        ));
        if ($documento->tamano > 0) {
            $respuesta->headers->set('Content-Length', (string) $documento->tamano);
        }

        return $respuesta;
    }

    public function destroy(Documento $documento): RedirectResponse
    {
        $this->authorize('documentos.desactivar');

        // Baja lógica: se conserva el archivo y los metadatos (§13/§14).
        $documento->delete();

        return back()->with('exito', 'Documento eliminado.');
    }
}
