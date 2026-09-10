<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normas aplicadas a una orden de mantenimiento. Especificación v2.0 §8.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mantenimiento_norma', function (Blueprint $table) {
            $table->foreignId('mantenimiento_id')->constrained('mantenimientos')->cascadeOnDelete();
            $table->foreignId('norma_id')->constrained('normas')->cascadeOnDelete();
            $table->primary(['mantenimiento_id', 'norma_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mantenimiento_norma');
    }
};
