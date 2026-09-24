<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RespuestaFormato extends Model
{
    use ConvierteMayusculas, HasFactory;

    protected $table = 'respuestas_formato';

    public $timestamps = false;

    protected $fillable = [
        'mantenimiento_id', 'formato_id', 'campo_id', 'valor_texto', 'valor_json', 'respondido_por', 'respondido_at',
    ];

    protected function camposMayusculas(): array
    {
        return ['valor_texto'];
    }

    protected function casts(): array
    {
        return [
            'valor_json' => 'array',
            'respondido_at' => 'datetime',
        ];
    }

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function formato(): BelongsTo
    {
        return $this->belongsTo(Formato::class);
    }

    public function campo(): BelongsTo
    {
        return $this->belongsTo(CampoFormato::class, 'campo_id');
    }

    public function respondidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'respondido_por');
    }
}
