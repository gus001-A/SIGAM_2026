<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Órdenes de mantenimiento (preventivo / correctivo / urgente). Especificación v2.0 §5.12 / §8.
 * Estado y prioridad son independientes (regla de negocio §7).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 40)->unique();
            $table->foreignId('solicitud_id')->nullable()->constrained('solicitudes_mantenimiento')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('planes_mantenimiento')->nullOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('tipo_id')->constrained('tipos_mantenimiento')->cascadeOnDelete();
            $table->foreignId('prioridad_id')->nullable()->constrained('prioridades')->nullOnDelete();
            $table->foreignId('estado_id')->constrained('estados_mantenimiento')->cascadeOnDelete();

            // Ciclo de fechas
            $table->timestamp('programado_inicio')->nullable();
            $table->timestamp('programado_fin')->nullable();
            $table->timestamp('autorizado_at')->nullable();
            $table->timestamp('iniciado_at')->nullable();
            $table->timestamp('completado_at')->nullable();
            $table->timestamp('supervisado_at')->nullable();
            $table->timestamp('cerrado_at')->nullable();

            $table->text('problema_reportado')->nullable();
            $table->text('diagnostico')->nullable();
            $table->text('descripcion_trabajo')->nullable(); // actividades realizadas
            $table->text('observaciones')->nullable();
            $table->string('condicion_final')->nullable();   // condición final del equipo
            $table->decimal('costo_mano_obra', 12, 2)->nullable();
            $table->decimal('costo_otros', 12, 2)->nullable();

            $table->foreignId('creado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('autorizado_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('supervisor_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['estado_id', 'prioridad_id', 'programado_inicio']);
            $table->index(['sucursal_id', 'tipo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimientos');
    }
};
