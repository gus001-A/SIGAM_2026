<?php

namespace App\Models;

use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Ubicación jerárquica dentro de una sucursal. Especificación v2.0 §5.6.
 */
class Ubicacion extends Model
{
    use EsAuditable, HasFactory, SoftDeletes, TieneEstadoActivo;

    protected $table = 'ubicaciones';

    protected $fillable = [
        'sucursal_id', 'padre_id', 'tipo_id', 'codigo', 'nombre', 'descripcion', 'profundidad', 'ruta', 'estado',
    ];

    protected function casts(): array
    {
        return ['profundidad' => 'integer'];
    }

    public function auditoriaModulo(): string
    {
        return 'ubicaciones';
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoUbicacion::class, 'tipo_id');
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'padre_id');
    }

    public function hijas(): HasMany
    {
        return $this->hasMany(self::class, 'padre_id');
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    /** IDs de todos los ancestros, para prevenir ciclos en el árbol (RF-024). */
    public function idsAncestros(): array
    {
        $ids = [];
        $nodo = $this->padre;
        while ($nodo) {
            $ids[] = $nodo->id;
            $nodo = $nodo->padre;
        }

        return $ids;
    }
}
