<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Archivo con metadatos. Especificación v2.0 §14. La relación con las entidades
 * del dominio se hace vía la tabla polimórfica `documento_relacionado`.
 */
class Documento extends Model
{
    use ConvierteMayusculas, HasFactory, SoftDeletes;

    protected $table = 'documentos';

    protected $fillable = [
        'disco', 'ruta', 'nombre_original', 'titulo', 'categoria',
        'tipo_mime', 'tamano', 'checksum', 'visibilidad', 'vence_at', 'subido_por',
    ];

    protected function camposMayusculas(): array
    {
        return ['nombre_original', 'titulo'];
    }

    protected function casts(): array
    {
        return [
            'tamano' => 'integer',
            'vence_at' => 'date',
        ];
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'subido_por');
    }

    public function esPublico(): bool
    {
        return $this->visibilidad === 'publico';
    }

    /**
     * URL para mostrar/descargar el documento. Deliberadamente NO usa
     * `Storage::disk($this->disco)->url()`: el disco `local` (documentos
     * privados, el caso normal) no tiene una URL pública configurada — ese
     * archivo vive fuera de `public/`, así que una URL de filesystem directa
     * simplemente no carga (404). Se sirve siempre por la ruta autenticada
     * que ya valida permisos y hace streaming del archivo real.
     */
    public function url(): string
    {
        return route('documentos.ver', $this->id);
    }
}
