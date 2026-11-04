<?php

namespace App\Support;

use App\Models\Documento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Adjunta un archivo de evidencia a cualquier entidad con el trait
 * `TieneDocumentos` — usado por las bajas de equipo y por las transiciones
 * de mantenimiento que exigen evidencia (§13/§14).
 */
class Documentos
{
    public static function adjuntarEvidencia(Model $entidad, UploadedFile $archivo, int $usuarioId, string $titulo, string $rol = 'evidencia'): Documento
    {
        $documento = Documento::create([
            ...self::guardar($archivo),
            'titulo' => $titulo,
            'categoria' => 'evidencia',
            'visibilidad' => 'privado',
            'subido_por' => $usuarioId,
        ]);

        $entidad->documentos()->attach($documento->id, ['rol' => $rol]);

        return $documento;
    }

    /**
     * Sube el archivo al disco de documentos configurado (`DOCUMENTOS_DISK`:
     * local o la nube) y regresa los datos del archivo para `Documento`.
     *
     * @return array{disco: string, ruta: string, nombre_original: string, tipo_mime: ?string, tamano: int|false, checksum: string}
     */
    public static function guardar(UploadedFile $archivo): array
    {
        $disco = config('filesystems.documentos', 'local');
        $ruta = $archivo->store('documentos/'.now()->format('Y/m'), $disco);

        throw_unless($ruta, RuntimeException::class, "No se pudo guardar el archivo en el disco [{$disco}].");

        return [
            'disco' => $disco,
            'ruta' => $ruta,
            'nombre_original' => $archivo->getClientOriginalName(),
            'tipo_mime' => $archivo->getClientMimeType(),
            'tamano' => $archivo->getSize(),
            'checksum' => hash_file('sha256', $archivo->getRealPath()),
        ];
    }
}
