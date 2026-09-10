<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga CSV con BOM UTF-8 para que Excel respete ñ, acentos y signos de
 * puntuación al abrir el archivo (sin el BOM, Excel asume Windows-1252).
 */
class ExportadorCsv
{
    /**
     * @param  list<string|int>  $encabezados
     * @param  iterable<int, array<int|string, mixed>>  $filas
     */
    public static function descargar(string $nombreArchivo, array $encabezados, iterable $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($encabezados, $filas): void {
            $salida = fopen('php://output', 'w');

            // BOM UTF-8: hace que Excel interprete el archivo como UTF-8.
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, $encabezados);
            foreach ($filas as $fila) {
                fputcsv($salida, array_map(
                    fn ($v) => $v === null ? '' : (is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE)),
                    (array) $fila,
                ));
            }

            fclose($salida);
        }, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
