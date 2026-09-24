<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogos de limpieza hospitalaria (observaciones generales del cliente §18-19):
 * "Tipo de área" define cada cuántos días debe limpiarse (crítica/semi-crítica/no
 * crítica); "Tipo de limpieza" describe cuándo se realiza (rutinaria/terminal/
 * exhaustiva). Una ubicación puede clasificarse con ambos, opcionalmente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_area', function (Blueprint $table) {
            // El servidor local trae `default_storage_engine=MyISAM`, que limita
            // el índice único de `nombre` a 1000 bytes; el resto de las tablas
            // del sistema es InnoDB, así que se fuerza aquí explícitamente.
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nombre')->unique();
            $table->string('clave', 80)->unique();
            $table->string('descripcion')->nullable();
            $table->unsignedSmallInteger('dias_limpieza'); // periodicidad de limpieza, en días
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });

        Schema::create('tipos_limpieza', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('nombre')->unique();
            $table->string('clave', 80)->unique();
            $table->string('frecuencia')->nullable(); // p. ej. "Diaria", "Después de cada evento"
            $table->string('descripcion')->nullable();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
        });

        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->foreignId('tipo_area_id')->nullable()->after('tipo_id')->constrained('tipos_area')->nullOnDelete();
            $table->foreignId('tipo_limpieza_id')->nullable()->after('tipo_area_id')->constrained('tipos_limpieza')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_area_id');
            $table->dropConstrainedForeignId('tipo_limpieza_id');
        });

        Schema::dropIfExists('tipos_limpieza');
        Schema::dropIfExists('tipos_area');
    }
};
