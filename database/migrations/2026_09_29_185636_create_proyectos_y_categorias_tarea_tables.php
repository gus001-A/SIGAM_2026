<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos para clasificar tareas: proyecto (a qué proyecto pertenece) y
 * categoría (agrupación libre para tareas generales).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nombre')->unique();
            $table->string('clave', 80)->unique();
            $table->string('descripcion')->nullable();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });

        Schema::create('categorias_tarea', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nombre')->unique();
            $table->string('clave', 80)->unique();
            $table->string('descripcion')->nullable();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_tarea');
        Schema::dropIfExists('proyectos');
    }
};
