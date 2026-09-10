<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcurrenciaPlanMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'ocurrencias_plan_mantenimiento';

    protected $fillable = [
        'plan_id', 'fecha_programada', 'estado', 'mantenimiento_id', 'generada_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_programada' => 'date',
            'generada_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanMantenimiento::class, 'plan_id');
    }

    public function mantenimiento(): BelongsTo
    {
        return $this->belongsTo(Mantenimiento::class);
    }
}
