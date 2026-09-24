<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo de seguimiento de tareas (Propuesta técnica SIGAM — anexo "TAREAS").
 * No está ligada a un equipo/ubicación: es un seguimiento administrativo general.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->text('descripcion');
            $table->date('fecha_limite');
            $table->string('estado', 20)->default('pendiente');
            $table->text('nota_avance')->nullable();
            $table->text('nota_cierre')->nullable();
            $table->text('nota_cancelacion')->nullable();
            $table->decimal('costo', 10, 2)->nullable();
            $table->timestamp('iniciada_at')->nullable();
            $table->timestamp('realizada_at')->nullable();
            $table->timestamp('cancelada_at')->nullable();
            $table->timestamps();

            $table->index(['estado', 'fecha_limite'], 'tareas_estado_fecha_limite_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
