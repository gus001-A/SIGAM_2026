<?php

namespace App\Support;

use App\Models\Documento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

/**
 * Adjunta un archivo de evidencia a cualquier entidad con el trait
 * `TieneDocumentos` — usado por las bajas de equipo y por las transiciones
 * de mantenimiento que exigen evidencia (§13/§14).
 */
class Documentos
{
    public static function adjuntarEvidencia(Model $entidad, UploadedFile $archivo, int $usuarioId, string $titulo, string $rol = 'evidencia'): Documento
    {
        $ruta = $archivo->store('documentos/'.now()->format('Y/m'), 'local');

        $documento = Documento::create([
            'disco' => 'local',
            'ruta' => $ruta,
            'nombre_original' => $archivo->getClientOriginalName(),
            'titulo' => $titulo,
            'categoria' => 'evidencia',
            'tipo_mime' => $archivo->getClientMimeType(),
            'tamano' => $archivo->getSize(),
            'checksum' => hash_file('sha256', $archivo->getRealPath()),
            'visibilidad' => 'privado',
            'subido_por' => $usuarioId,
        ]);

        $entidad->documentos()->attach($documento->id, ['rol' => $rol]);

        return $documento;
    }
}
