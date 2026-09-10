<?php

namespace App\Models;

use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneDocumentos;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sucursal extends Model
{
    use EsAuditable, HasFactory, SoftDeletes, TieneDocumentos, TieneEstadoActivo;

    protected $table = 'sucursales';

    protected $fillable = [
        'codigo', 'nombre', 'direccion', 'telefono', 'correo', 'responsable_id', 'estado', 'notas',
    ];

    public function auditoriaModulo(): string
    {
        return 'sucursales';
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsable_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }

    public function ubicaciones(): HasMany
    {
        return $this->hasMany(Ubicacion::class);
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }

    public function solicitudes(): HasMany
    {
        return $this->hasMany(SolicitudMantenimiento::class);
    }

    public function mantenimientos(): HasMany
    {
        return $this->hasMany(Mantenimiento::class);
    }
}
