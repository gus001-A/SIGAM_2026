<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReprogramacionMantenimiento extends Model
{
    use ConvierteMayusculas, HasFactory;

    protected $table = 'reprogramaciones_mantenimiento';

    public $timestamps = false;

    protected $fillable = [
        'mantenimiento_id', 'inicio_anterior', 'fin_anterior', 'inicio_nuevo', 'fin_nuevo', 'motivo', 'reprogramado_por', 'created_at',
    ];

    protected function camposMayusculas(): array
    {
        return ['motivo'];
    }

    protected function casts(): array
    {
        return [
            'inicio_anterior' => 'datetime',
            'fin_anterior' => 'datetime',
            'inicio_nuevo' => 'datetime',
            'fin_nuevo' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function reprogramadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'reprogramado_por');
    }
}
