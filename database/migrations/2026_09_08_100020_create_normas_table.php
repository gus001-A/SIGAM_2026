<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normas / procedimientos aplicables. Especificación v2.0 §5.14 / §8.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('normas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 80)->unique();
            $table->string('nombre');
            $table->string('version', 40)->nullable();
            $table->text('descripcion')->nullable();
            $table->date('fecha_vigencia')->nullable();
            $table->date('fecha_revision')->nullable();
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->nullOnDelete();
            $table->string('estado', 20)->default('activo')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('normas');
    }
};
