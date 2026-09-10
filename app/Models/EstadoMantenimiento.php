<?php

namespace App\Models;

use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Estados del ciclo de una orden. Especificación v2.0 §7 + Propuesta SIGAM §7.3.
 * clave: solicitado | autorizado | asignado | en_proceso | realizado |
 *        supervisado | cerrado | cancelado | reprogramado |
 *        en_espera_refaccion | fuera_de_servicio
 */
class EstadoMantenimiento extends Model
{
    use HasFactory, TieneEstadoActivo;

    protected $table = 'estados_mantenimiento';

    protected $fillable = ['nombre', 'clave', 'descripcion', 'orden', 'es_terminal', 'es_abierto', 'estado'];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'es_terminal' => 'boolean',
            'es_abierto' => 'boolean',
        ];
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class, 'estado_id');
    }
}
