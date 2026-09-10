<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitudes de mantenimiento (correctivo). Especificación v2.0 §5.10 / §8 / RF-043.
 * Se convierten en una orden de mantenimiento tras priorización / autorización.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 40)->unique();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('solicitado_por')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('prioridad_id')->nullable()->constrained('prioridades')->nullOnDelete();
            $table->foreignId('estado_id')->constrained('estados_mantenimiento')->cascadeOnDelete();
            $table->text('descripcion');
            $table->date('fecha_requerida')->nullable();
            $table->timestamp('solicitado_at')->useCurrent();
            $table->foreignId('revisado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('revisado_at')->nullable();
            $table->string('motivo_rechazo')->nullable();
            $table->timestamps();

            $table->index(['estado_id', 'prioridad_id', 'fecha_requerida'], 'solicitudes_mant_estado_prioridad_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_mantenimiento');
    }
};
