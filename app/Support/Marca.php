<?php

namespace App\Support;

/**
 * Recursos de marca SIGAM para documentos generados (PDF, correos, etc.).
 */
class Marca
{
    /** El logotipo SIGAM como data URI base64, listo para <img src>. */
    public static function logoDataUri(): ?string
    {
        foreach (['logo-web-transparente.png', 'logo-web.jpeg', 'logo-sigam.png', 'LOGO2.jpeg'] as $archivo) {
            $ruta = public_path('images/'.$archivo);
            if (is_file($ruta)) {
                $mime = str_ends_with($archivo, '.png') ? 'image/png' : 'image/jpeg';

                return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($ruta));
            }
        }

        return null;
    }
}
