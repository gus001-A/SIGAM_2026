<?php

namespace App\Models;

use App\Models\Concerns\ConvierteMayusculas;
use App\Models\Concerns\EsAuditable;
use App\Models\Concerns\TieneDocumentos;
use App\Models\Concerns\TieneEstadoActivo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    use ConvierteMayusculas, EsAuditable, HasFactory, SoftDeletes, TieneDocumentos, TieneEstadoActivo;

    protected $table = 'proveedores';

    protected $fillable = [
        'razon_social', 'nombre_comercial', 'rfc', 'contacto', 'telefono', 'correo', 'direccion', 'especialidad', 'estado', 'notas',
    ];

    public function auditoriaModulo(): string
    {
        return 'proveedores';
    }

    protected function camposMayusculas(): array
    {
        return ['razon_social', 'nombre_comercial', 'rfc', 'contacto', 'direccion', 'especialidad', 'notas'];
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class);
    }
}
