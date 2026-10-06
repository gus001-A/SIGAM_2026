<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Workaround para cuando NO tienes acceso SSH/terminal para correr
 * `php artisan storage:link` (típico en hosting compartido con solo FTP).
 *
 * Sirve archivos directo desde storage/app/public, imitando exactamente
 * lo que el symlink public/storage -> storage/app/public haría — así
 * NINGÚN controlador existente que use asset('storage/...') o
 * Storage::url(...) necesita cambiar, porque la URL final es idéntica.
 *
 * En cuanto puedas correr `php artisan storage:link` (ya sea tú por
 * Terminal del panel, o lo haga soporte), puedes borrar esta ruta y este
 * controlador sin tocar nada más — todo seguirá funcionando igual.
 */
class StorageProxyController extends Controller
{
    public function servir(Request $request, string $ruta): StreamedResponse
    {
        // Evita que alguien intente escapar la carpeta con "../../"
        $rutaSegura = str_replace(['..', "\0"], '', $ruta);

        abort_unless(Storage::disk('public')->exists($rutaSegura), 404);

        $rutaCompleta = Storage::disk('public')->path($rutaSegura);
        $mime = Storage::disk('public')->mimeType($rutaSegura) ?: 'application/octet-stream';

        return response()->stream(function () use ($rutaCompleta) {
            $stream = fopen($rutaCompleta, 'rb');
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Length' => Storage::disk('public')->size($rutaSegura),
            // Cachea en el navegador por 1 día — las imágenes de storage no
            // suelen cambiar de contenido una vez subidas.
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
