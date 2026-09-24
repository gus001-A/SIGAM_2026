<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notificación in-app del dominio SIGAM. Especificación v2.0 §12.
 */
class Notificacion extends Model
{
    use ConvierteMayusculas, HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = ['usuario_id', 'tipo', 'titulo', 'cuerpo', 'datos', 'leida_at'];

    protected function camposMayusculas(): array
    {
        return ['titulo', 'cuerpo'];
    }

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'leida_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function scopeNoLeidas(Builder $query): Builder
    {
        return $query->whereNull('leida_at');
    }

    public function marcarLeida(): bool
    {
        return $this->leida_at !== null || $this->forceFill(['leida_at' => now()])->save();
    }
}
