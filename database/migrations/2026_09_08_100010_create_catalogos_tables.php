<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos configurables. Especificación v2.0 §5.13 y §8; Propuesta SIGAM Anexo A.
 * Todos comparten: nombre, descripcion y estado (activo | inactivo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_ubicacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('clave', 80)->unique();
            $table->string('descripcion')->nullable();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });

        Schema::create('tipos_equipo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('clave', 80)->unique();
            $table->string('descripcion')->nullable();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });

        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('descripcion')->nullable();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });

        Schema::create('estados_equipo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('clave', 80)->unique(); // operativo | mantenimiento | fuera_de_servicio | baja
            $table->string('descripcion')->nullable();
            $table->string('color', 20)->nullable();
            $table->boolean('es_operativo')->default(true);
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });

        Schema::create('tipos_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('clave', 80)->unique();
            $table->string('categoria', 30); // preventivo | correctivo | urgente | inspeccion
            $table->string('descripcion')->nullable();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });

        Schema::create('prioridades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('clave', 80)->unique(); // normal | prioritaria | urgente | critica
            $table->unsignedTinyInteger('nivel')->default(1); // mayor nivel = mayor prioridad
            $table->unsignedInteger('minutos_respuesta')->nullable(); // objetivo SLA de respuesta
            $table->string('color', 20)->nullable();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });

        Schema::create('estados_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('clave', 80)->unique(); // solicitado | autorizado | asignado | en_proceso | realizado | supervisado | cerrado | cancelado | reprogramado | en_espera_refaccion | fuera_de_servicio
            $table->string('descripcion')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('es_terminal')->default(false); // cerrado / cancelado
            $table->boolean('es_abierto')->default(true);    // cuenta como pendiente en el dashboard
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });

        Schema::create('materiales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 60)->nullable()->unique();
            $table->string('nombre');
            $table->string('unidad', 30)->default('pza'); // pza | m | lt | kg ...
            $table->decimal('costo_referencia', 12, 2)->nullable();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiales');
        Schema::dropIfExists('estados_mantenimiento');
        Schema::dropIfExists('prioridades');
        Schema::dropIfExists('tipos_mantenimiento');
        Schema::dropIfExists('estados_equipo');
        Schema::dropIfExists('marcas');
        Schema::dropIfExists('tipos_equipo');
        Schema::dropIfExists('tipos_ubicacion');
    }
};
