<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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

    public function url(): ?string
    {
        return Storage::disk($this->disco)->url($this->ruta);
    }
}
