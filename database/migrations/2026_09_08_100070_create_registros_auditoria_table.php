<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora central de auditoría. Especificación v2.0 §8 / §13 / RF-062..064.
 * Los usuarios operativos no pueden alterar estos registros (solo inserción).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_auditoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('accion', 60)->index();  // acceso | salida | crear | actualizar | eliminar | asignar | reprogramar | cambio_estado | exportar | descargar ...
            $table->string('modulo', 60)->index();
            $table->nullableMorphs('auditable');    // auditable_type, auditable_id
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('navegador')->nullable(); // user agent
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->json('metadatos')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_auditoria');
    }
};
