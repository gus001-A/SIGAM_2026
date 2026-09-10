<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Constructor de formatos / checklists configurables. Especificación v2.0 §5.16 / §8.
 * Propuesta SIGAM §8: crear formularios sin tocar código fuente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formatos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->string('version', 40)->default('1.0');
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('campos_formato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formato_id')->constrained('formatos')->cascadeOnDelete();
            $table->string('tipo', 30); // texto | area_texto | numero | fecha | seleccion | checkbox | foto | firma
            $table->string('etiqueta');
            $table->string('clave', 80);
            $table->json('opciones')->nullable(); // opciones para seleccion / checkbox
            $table->boolean('obligatorio')->default(false);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->string('ayuda')->nullable();
            $table->timestamps();

            $table->unique(['formato_id', 'clave']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campos_formato');
        Schema::dropIfExists('formatos');
    }
};
