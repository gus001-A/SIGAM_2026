<?php

namespace App\Models;

use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory, TieneEstadoActivo;

    protected $table = 'materiales';

    protected $fillable = ['codigo', 'nombre', 'unidad', 'costo_referencia', 'estado'];

    protected function casts(): array
    {
        return ['costo_referencia' => 'decimal:2'];
    }

    public function usos(): HasMany
    {
        return $this->hasMany(MaterialMantenimiento::class);
    }
}
