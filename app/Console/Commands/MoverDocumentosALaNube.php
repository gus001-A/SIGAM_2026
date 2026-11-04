<?php

namespace App\Console\Commands;

use App\Models\Documento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Copia los archivos que siguen en el servidor (disco `local`) al disco de
 * documentos configurado en `DOCUMENTOS_DISK` (la nube) y actualiza cada
 * registro para que apunte allá. El archivo local no se borra: si algo sale
 * mal, el documento sigue funcionando desde el servidor.
 *
 * Usa `saveQuietly()`: es una mudanza técnica, no una edición real, así que
 * no debe generar entradas de auditoría.
 */
class MoverDocumentosALaNube extends Command
{
    protected $signature = 'sigam:documentos-a-la-nube {--dry-run : Solo cuenta cuántos se moverían, sin subir nada}';

    protected $description = 'Sube a la nube (DOCUMENTOS_DISK) los documentos y fotos que siguen guardados en el servidor';

    public function handle(): int
    {
        $destino = config('filesystems.documentos');
        if ($destino === 'local') {
            $this->error('DOCUMENTOS_DISK sigue en "local". Configura la nube en el .env antes de mover los archivos.');

            return self::FAILURE;
        }

        $local = Storage::disk('local');
        $nube = Storage::disk($destino);
        $movidos = $faltantes = $errores = 0;

        Documento::withTrashed()->where('disco', 'local')->orderBy('id')->each(
            function (Documento $doc) use ($local, $nube, $destino, &$movidos, &$faltantes, &$errores): void {
                if (! $local->exists($doc->ruta)) {
                    $faltantes++;
                    $this->warn("  #{$doc->id} sin archivo en el servidor: {$doc->ruta}");

                    return;
                }

                if ($this->option('dry-run')) {
                    $movidos++;

                    return;
                }

                try {
                    $flujo = $local->readStream($doc->ruta);
                    $nube->writeStream($doc->ruta, $flujo);
                    if (is_resource($flujo)) {
                        fclose($flujo);
                    }

                    $doc->disco = $destino;
                    $doc->saveQuietly();
                    $movidos++;
                } catch (Throwable $e) {
                    $errores++;
                    $this->error("  #{$doc->id} no se pudo subir: {$e->getMessage()}");
                }
            },
        );

        $verbo = $this->option('dry-run') ? 'Se moverían' : 'Movidos';
        $this->info("{$verbo}: {$movidos} · Sin archivo en el servidor: {$faltantes} · Con error: {$errores}");

        return $errores > 0 ? self::FAILURE : self::SUCCESS;
    }
}
