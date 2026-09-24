<?php

namespace App\Support;

use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Marca;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use App\Models\Ubicacion;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Carga masiva de equipos desde un archivo .xlsx / .csv (RF-031).
 * La primera fila del archivo son los encabezados (ver COLUMNAS).
 */
class ImportadorEquipos
{
    /** Encabezados esperados, en orden, para la plantilla. */
    public const COLUMNAS = [
        'codigo_activo', 'descripcion', 'codigo_barras', 'tipo', 'marca', 'modelo',
        'numero_serie', 'sucursal', 'ubicacion', 'estado', 'proveedor', 'responsable',
        'fecha_adquisicion', 'numero_factura', 'valor_adquisicion', 'garantia_hasta',
        'vida_util', 'notas',
    ];

    private const RE_CODIGO = '/^[\p{L}\p{N}\s._\/\-#]+$/u';

    private const RE_TEXTO = '/^[^<>{}\\\\]+$/u';

    /**
     * @return array{creados: int, errores: array<int, array{fila: int, mensajes: array<int, string>}>}
     */
    public function procesar(UploadedFile $archivo, int $usuarioId): array
    {
        $filas = $this->leerFilas($archivo);

        $creados = 0;
        $errores = [];

        foreach ($filas as $numero => $fila) {
            try {
                $datos = $this->mapear($fila);
                $mensajes = $this->validar($datos);

                if ($mensajes !== []) {
                    $errores[] = ['fila' => $numero, 'mensajes' => $mensajes];

                    continue;
                }

                DB::transaction(function () use ($datos, $usuarioId): void {
                    $equipo = Equipo::create($this->resolverReferencias($datos));

                    if ($equipo->ubicacion_id) {
                        $equipo->historialUbicacion()->create([
                            'ubicacion_origen_id' => null,
                            'ubicacion_destino_id' => $equipo->ubicacion_id,
                            'cambiado_por' => $usuarioId,
                            'motivo' => 'Alta por carga masiva',
                            'cambiado_at' => now(),
                        ]);
                    }
                });

                $creados++;
            } catch (\Throwable $e) {
                $errores[] = ['fila' => $numero, 'mensajes' => ['Error inesperado: '.$e->getMessage()]];
            }
        }

        return ['creados' => $creados, 'errores' => $errores];
    }

    /**
     * @return array<int, array<string, string>> filas indexadas por su número (base 2)
     */
    private function leerFilas(UploadedFile $archivo): array
    {
        $reader = $this->lectorPara($archivo);
        $reader->open($archivo->getRealPath());

        $filas = [];
        $encabezados = null;

        foreach ($reader->getSheetIterator() as $hoja) {
            foreach ($hoja->getRowIterator() as $i => $row) {
                $valores = array_map(fn ($v) => is_string($v) ? trim($this->aUtf8($v)) : $v, $row->toArray());

                if ($i === 1) {
                    $encabezados = array_map(
                        fn ($h) => Str::of((string) $h)->lower()->ascii()->replace(' ', '_')->toString(),
                        $valores,
                    );

                    continue;
                }

                if (count(array_filter($valores, fn ($v) => $v !== null && $v !== '')) === 0) {
                    continue;
                }

                $fila = [];
                foreach ($encabezados as $col => $clave) {
                    $fila[$clave] = $valores[$col] ?? null;
                }
                $filas[$i] = $fila;
            }
            break; // solo la primera hoja
        }

        $reader->close();

        return $filas;
    }

    /**
     * Normaliza el texto a UTF-8. Si el archivo se guardó en Windows-1252
     * (Excel en Windows), la ñ y los acentos llegan mal; los convertimos.
     */
    private function aUtf8(string $valor): string
    {
        // Quita un BOM UTF-8 al principio si existe.
        $valor = preg_replace('/^\xEF\xBB\xBF/', '', $valor) ?? $valor;

        if ($valor === '' || mb_check_encoding($valor, 'UTF-8')) {
            return $valor;
        }

        return mb_convert_encoding($valor, 'UTF-8', 'Windows-1252');
    }

    /** Elige el lector según la extensión declarada del archivo subido. */
    private function lectorPara(UploadedFile $archivo): ReaderInterface
    {
        $ext = strtolower($archivo->getClientOriginalExtension() ?: pathinfo($archivo->getClientOriginalName(), PATHINFO_EXTENSION));

        return match ($ext) {
            'csv', 'txt' => new CsvReader,
            'xlsx', 'xls' => new XlsxReader,
            default => throw new UnsupportedTypeException("Formato no admitido: .{$ext}"),
        };
    }

    /**
     * @param  array<string, mixed>  $fila
     * @return array<string, mixed>
     */
    private function mapear(array $fila): array
    {
        $val = fn (string $k) => isset($fila[$k]) && $fila[$k] !== '' ? $fila[$k] : null;
        $fecha = function (string $k) use ($fila) {
            $v = $fila[$k] ?? null;
            if ($v instanceof \DateTimeInterface) {
                return Carbon::instance($v)->toDateString();
            }
            if (! $v) {
                return null;
            }

            try {
                return Carbon::parse((string) $v)->toDateString();
            } catch (\Throwable) {
                return (string) $v; // que falle en validación
            }
        };

        return [
            'codigo_activo' => $val('codigo_activo'),
            'descripcion' => $val('descripcion'),
            'codigo_barras' => $val('codigo_barras'),
            'tipo' => $val('tipo'),
            'marca' => $val('marca'),
            'modelo' => $val('modelo'),
            'numero_serie' => $val('numero_serie'),
            'sucursal' => $val('sucursal'),
            'ubicacion' => $val('ubicacion'),
            'estado' => $val('estado'),
            'proveedor' => $val('proveedor'),
            'responsable' => $val('responsable'),
            'fecha_adquisicion' => $fecha('fecha_adquisicion'),
            'numero_factura' => $val('numero_factura'),
            'valor_adquisicion' => $val('valor_adquisicion') !== null ? (string) $val('valor_adquisicion') : null,
            'garantia_hasta' => $fecha('garantia_hasta'),
            'vida_util' => $val('vida_util'),
            'notas' => $val('notas'),
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<int, string>
     */
    private function validar(array $datos): array
    {
        $v = Validator::make($datos, [
            'codigo_activo' => ['required', 'string', 'max:80', 'regex:'.self::RE_CODIGO, 'unique:equipos,codigo_activo'],
            'descripcion' => ['required', 'string', 'max:255', 'regex:'.self::RE_TEXTO],
            'codigo_barras' => ['nullable', 'string', 'max:120', 'regex:'.self::RE_CODIGO],
            'numero_serie' => ['nullable', 'string', 'max:255', 'regex:'.self::RE_CODIGO],
            'modelo' => ['nullable', 'string', 'max:255', 'regex:'.self::RE_CODIGO],
            'numero_factura' => ['nullable', 'string', 'max:255', 'regex:'.self::RE_CODIGO],
            'sucursal' => ['required', 'string'],
            'fecha_adquisicion' => ['nullable', 'date', 'before_or_equal:today'],
            'garantia_hasta' => ['nullable', 'date', 'after_or_equal:fecha_adquisicion'],
            'valor_adquisicion' => ['nullable', 'numeric', 'min:0'],
        ], [
            'codigo_activo.unique' => 'Ya existe un equipo con ese código.',
            'codigo_activo.regex' => 'El código solo admite letras, números y . - _ / #.',
            'descripcion.regex' => 'La descripción no puede contener < > { } \\.',
            'fecha_adquisicion.before_or_equal' => 'La fecha de adquisición no puede ser futura.',
            'garantia_hasta.after_or_equal' => 'La garantía debe vencer después de la adquisición.',
        ]);

        $mensajes = $v->errors()->all();

        if ($datos['sucursal'] && ! Sucursal::where('nombre', $this->mayus($datos['sucursal']))->exists()) {
            $mensajes[] = "La sucursal «{$datos['sucursal']}» no existe.";
        }

        return $mensajes;
    }

    /**
     * Los catálogos y sucursales se guardan en MAYÚSCULAS (observaciones generales
     * del cliente); el archivo de Excel puede traer el texto en cualquier caja,
     * así que las búsquedas por nombre normalizan antes de comparar.
     */
    private function mayus(string $valor): string
    {
        return mb_strtoupper($valor, 'UTF-8');
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function resolverReferencias(array $datos): array
    {
        $sucursal = Sucursal::where('nombre', $this->mayus($datos['sucursal']))->firstOrFail();

        $tipoId = $datos['tipo']
            ? TipoEquipo::firstOrCreate(['nombre' => $this->mayus($datos['tipo'])], ['clave' => Str::slug($datos['tipo'], '_'), 'estado' => 'activo'])->id
            : null;
        $marcaId = $datos['marca']
            ? Marca::firstOrCreate(['nombre' => $this->mayus($datos['marca'])], ['estado' => 'activo'])->id
            : null;
        $estadoId = $datos['estado']
            ? EstadoEquipo::where('nombre', $this->mayus($datos['estado']))->value('id')
            : null;
        $proveedorId = $datos['proveedor']
            ? Proveedor::where('razon_social', $this->mayus($datos['proveedor']))->value('id')
            : null;
        $responsableId = $datos['responsable']
            ? Usuario::where('email', $datos['responsable'])->value('id')
            : null;
        $ubicacionId = $datos['ubicacion']
            ? Ubicacion::where('sucursal_id', $sucursal->id)->where('nombre', $this->mayus($datos['ubicacion']))->value('id')
            : null;

        return [
            'codigo_activo' => $datos['codigo_activo'],
            'descripcion' => $datos['descripcion'],
            'codigo_barras' => $datos['codigo_barras'],
            'tipo_id' => $tipoId,
            'marca_id' => $marcaId,
            'modelo' => $datos['modelo'],
            'numero_serie' => $datos['numero_serie'],
            'sucursal_id' => $sucursal->id,
            'ubicacion_id' => $ubicacionId,
            'proveedor_id' => $proveedorId,
            'responsable_id' => $responsableId,
            'estado_id' => $estadoId,
            'fecha_adquisicion' => $datos['fecha_adquisicion'],
            'numero_factura' => $datos['numero_factura'],
            'valor_adquisicion' => $datos['valor_adquisicion'],
            'garantia_hasta' => $datos['garantia_hasta'],
            'vida_util' => $datos['vida_util'],
            'notas' => $datos['notas'],
        ];
    }
}
