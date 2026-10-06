<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clasificación de la tarea: general (por defecto), por proyecto o por
 * categoría. Solo una de las dos referencias aplica según `clasificacion`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->string('clasificacion', 20)->default('general')->after('prioridad_id');
            $table->foreignId('proyecto_id')->nullable()->after('clasificacion')
                ->constrained('proyectos')->nullOnDelete();
            $table->foreignId('categoria_tarea_id')->nullable()->after('proyecto_id')
                ->constrained('categorias_tarea')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proyecto_id');
            $table->dropConstrainedForeignId('categoria_tarea_id');
            $table->dropColumn('clasificacion');
        });
    }
};
