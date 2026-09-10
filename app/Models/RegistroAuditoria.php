<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registro de la bitácora de auditoría. Especificación v2.0 §13.
 * Solo se inserta: los usuarios operativos no pueden modificar ni borrar.
 */
class RegistroAuditoria extends Model
{
    use HasFactory;

    protected $table = 'registros_auditoria';

    public const UPDATED_AT = null; // solo created_at

    protected $fillable = [
        'usuario_id', 'accion', 'modulo', 'auditable_type', 'auditable_id',
        'sucursal_id', 'ip', 'navegador', 'valores_anteriores', 'valores_nuevos', 'metadatos',
    ];

    protected function casts(): array
    {
        return [
            'valores_anteriores' => 'array',
            'valores_nuevos' => 'array',
            'metadatos' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
