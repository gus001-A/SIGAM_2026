<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialMantenimiento extends Model
{
    use ConvierteMayusculas, HasFactory;

    protected $table = 'materiales_mantenimiento';

    protected $fillable = [
        'mantenimiento_id', 'material_id', 'descripcion', 'cantidad', 'unidad', 'costo_unitario', 'notas',
    ];

    protected function camposMayusculas(): array
    {
        return ['descripcion', 'unidad', 'notas'];
    }

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'costo_unitario' => 'decimal:2',
        ];
    }

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function getCostoTotalAttribute(): ?string
    {
        if ($this->costo_unitario === null) {
            return null;
        }

        return bcmul((string) $this->costo_unitario, (string) $this->cantidad, 2);
    }
}
