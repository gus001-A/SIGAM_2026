<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materiales / refacciones utilizadas en una orden. Especificación v2.0 §8 / §5.12.
 * material_id es opcional: permite capturar refacciones fuera de catálogo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiales_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_id')->constrained('mantenimientos')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materiales')->nullOnDelete();
            $table->string('descripcion')->nullable(); // usado cuando no hay material_id
            $table->decimal('cantidad', 12, 2)->default(1);
            $table->string('unidad', 30)->default('pza');
            $table->decimal('costo_unitario', 12, 2)->nullable();
            $table->string('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales_mantenimiento');
    }
};
