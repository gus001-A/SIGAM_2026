<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Planes de mantenimiento preventivo por equipo. Especificación v2.0 §5.8 / §8 / RF-041.
 * Propuesta SIGAM §7.2: periodicidad semanal..anual, por días o regla personalizada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('tipo_mantenimiento_id')->constrained('tipos_mantenimiento')->cascadeOnDelete();
            $table->string('nombre')->nullable();
            $table->string('tipo_frecuencia', 20); // dias | semanal | mensual | bimestral | trimestral | semestral | anual | personalizada
            $table->unsignedInteger('valor_frecuencia')->default(1); // p.ej. cada N días / meses
            $table->json('regla_personalizada')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('proxima_fecha')->nullable()->index();
            $table->unsignedSmallInteger('dias_aviso_anticipado')->default(7);
            $table->foreignId('norma_id')->nullable()->constrained('normas')->nullOnDelete();
            $table->foreignId('formato_id')->nullable()->constrained('formatos')->nullOnDelete();
            $table->foreignId('prioridad_id')->nullable()->constrained('prioridades')->nullOnDelete();
            $table->foreignId('tecnico_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_mantenimiento');
    }
};
