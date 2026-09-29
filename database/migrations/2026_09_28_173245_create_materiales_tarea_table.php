<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materiales usados en una tarea. Espejo de `materiales_mantenimiento`.
 * material_id es opcional: permite capturar insumos fuera de catálogo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiales_tarea', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tarea_id')->constrained('tareas')->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('materiales')->nullOnDelete();
            $table->string('descripcion')->nullable();
            $table->decimal('cantidad', 12, 2)->default(1);
            $table->string('unidad', 30)->default('pza');
            $table->decimal('costo_unitario', 12, 2)->nullable();
            $table->string('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales_tarea');
    }
};
