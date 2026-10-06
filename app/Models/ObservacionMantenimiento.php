<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class ObservacionMantenimiento extends Model
{
    use ConvierteMayusculas, HasFactory;

    protected $table = 'observaciones_mantenimiento';

    const UPDATED_AT = null;

    protected $fillable = ['mantenimiento_id', 'usuario_id', 'tipo', 'cuerpo', 'documento_evidencia_id'];

    protected function camposMayusculas(): array
    {
        return ['cuerpo'];
    }

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function evidencias(): MorphToMany
    {
        return $this->morphToMany(Documento::class, 'movimiento', 'evidencias_movimiento', 'movimiento_id', 'documento_id')
            ->withTimestamps();
    }
}
