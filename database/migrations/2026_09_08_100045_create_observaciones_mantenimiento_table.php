<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Observaciones / comentarios / bitácora de una orden. Especificación v2.0 §8 / §5.12.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observaciones_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_id')->constrained('mantenimientos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('tipo', 30)->default('comentario'); // comentario | diagnostico | actividad | supervision | sistema
            $table->text('cuerpo');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['mantenimiento_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones_mantenimiento');
    }
};
