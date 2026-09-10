<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notificaciones in-app del dominio SIGAM. Especificación v2.0 §8 / §12.
 * Modelo propio (no el sistema de Notifications de Laravel) para mantener el
 * esquema completamente en español.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('tipo', 60); // mantenimiento_proximo | mantenimiento_vencido | urgencia | asignacion | trabajo_terminado | pendiente_supervision | garantia_por_vencer
            $table->string('titulo');
            $table->text('cuerpo')->nullable();
            $table->json('datos')->nullable(); // enlace / ids de contexto
            $table->timestamp('leida_at')->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'leida_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
