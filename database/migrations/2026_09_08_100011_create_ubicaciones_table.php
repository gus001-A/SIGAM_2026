<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubicaciones jerárquicas por sucursal. Especificación v2.0 §5.6 / §8.
 * padre_id -> ubicaciones.id (árbol). RF-024: evitar ciclos (se valida en la app).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('padre_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $table->foreignId('tipo_id')->nullable()->constrained('tipos_ubicacion')->nullOnDelete();
            $table->string('codigo', 60);
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->unsignedSmallInteger('profundidad')->default(0);
            $table->string('ruta')->nullable(); // ruta materializada: "1/4/9"
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sucursal_id', 'codigo']);
            $table->index(['sucursal_id', 'padre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};
