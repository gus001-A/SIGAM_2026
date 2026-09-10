<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ocurrencias programadas de un plan preventivo. Especificación v2.0 §8 / RF-042.
 * Cada fecha calculada genera una ocurrencia; al ejecutarse enlaza con la orden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ocurrencias_plan_mantenimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('planes_mantenimiento')->cascadeOnDelete();
            $table->date('fecha_programada')->index();
            $table->string('estado', 20)->default('pendiente'); // pendiente | generada | realizada | omitida | vencida
            $table->foreignId('mantenimiento_id')->nullable()->constrained('mantenimientos')->nullOnDelete();
            $table->timestamp('generada_at')->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'fecha_programada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ocurrencias_plan_mantenimiento');
    }
};
