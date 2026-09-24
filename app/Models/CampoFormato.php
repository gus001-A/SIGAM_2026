<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampoFormato extends Model
{
    use ConvierteMayusculas, HasFactory;

    protected $table = 'campos_formato';

    protected $fillable = [
        'formato_id', 'tipo', 'etiqueta', 'clave', 'opciones', 'obligatorio', 'orden', 'ayuda',
    ];

    protected function camposMayusculas(): array
    {
        return ['etiqueta', 'ayuda'];
    }

    protected function casts(): array
    {
        return [
            'opciones' => 'array',
            'obligatorio' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function formato(): BelongsTo
    {
        return $this->belongsTo(Formato::class);
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaFormato::class, 'campo_id');
    }
}
