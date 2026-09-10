<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Activos / equipos. Especificación v2.0 §5.7-5.9 / §8.
 * RF-030: codigo_activo único. Propuesta SIGAM §6.1: identificación por QR / código de barras.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_activo', 80)->unique();       // código interno del activo
            $table->string('token_qr', 64)->unique();            // token opaco para escaneo QR
            $table->string('codigo_barras', 120)->nullable()->index();
            $table->string('descripcion');
            $table->foreignId('tipo_id')->nullable()->constrained('tipos_equipo')->nullOnDelete();
            $table->foreignId('marca_id')->nullable()->constrained('marcas')->nullOnDelete();
            $table->string('modelo')->nullable();
            $table->string('numero_serie')->nullable()->index();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('estado_id')->nullable()->constrained('estados_equipo')->nullOnDelete();
            $table->date('fecha_adquisicion')->nullable();
            $table->string('numero_factura')->nullable();
            $table->decimal('valor_adquisicion', 14, 2)->nullable();
            $table->date('garantia_hasta')->nullable();
            $table->json('especificaciones')->nullable(); // características / capacidad / especificaciones
            $table->string('vida_util')->nullable();      // vida útil estimada
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sucursal_id', 'ubicacion_id', 'estado_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipos');
    }
};
