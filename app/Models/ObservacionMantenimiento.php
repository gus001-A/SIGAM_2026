<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservacionMantenimiento extends Model
{
    use ConvierteMayusculas, HasFactory;

    protected $table = 'observaciones_mantenimiento';

    public $timestamps = false;

    protected $fillable = ['mantenimiento_id', 'usuario_id', 'tipo', 'cuerpo', 'created_at'];

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
}
