<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Respuestas capturadas de un formato / checklist en una orden. Especificación v2.0 §8.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('respuestas_formato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_id')->constrained('mantenimientos')->cascadeOnDelete();
            $table->foreignId('formato_id')->constrained('formatos')->cascadeOnDelete();
            $table->foreignId('campo_id')->constrained('campos_formato')->cascadeOnDelete();
            $table->text('valor_texto')->nullable();
            $table->json('valor_json')->nullable(); // selección múltiple / firma / metadatos de foto
            $table->foreignId('respondido_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('respondido_at')->useCurrent();

            $table->unique(['mantenimiento_id', 'campo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_formato');
    }
};
