<?php

namespace App\Support;

use App\Models\Documento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * Evidencias de un avance: varias fotos por movimiento de historial/bitácora.
 */
class Evidencias
{
    public const MAXIMO = 10;

    /**
     * @param  array<int, UploadedFile>  $archivos
     * @return Collection<int, Documento>
     */
    public static function adjuntar(Model $entidad, Model $movimiento, array $archivos, int $usuarioId, string $titulo): Collection
    {
        $documentos = collect($archivos)->map(fn (UploadedFile $archivo) => Documentos::adjuntarEvidencia(
            $entidad,
            $archivo,
            $usuarioId,
            $titulo,
        ));

        $movimiento->evidencias()->attach($documentos->pluck('id')->all());

        return $documentos;
    }
}
