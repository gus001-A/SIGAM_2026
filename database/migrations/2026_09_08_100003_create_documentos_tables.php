<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión documental. Especificación v2.0 §8 y §14.
 * documentos              = archivo físico + metadatos.
 * documento_relacionado   = relación polimórfica hacia equipos, sucursales,
 *                           mantenimientos, normas y proveedores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('disco', 40)->default('local');
            $table->string('ruta');
            $table->string('nombre_original');
            $table->string('titulo')->nullable();
            $table->string('categoria', 60)->nullable(); // manual | factura | garantia | norma | formato | evidencia | foto
            $table->string('tipo_mime', 120)->nullable();
            $table->unsignedBigInteger('tamano')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('visibilidad', 20)->default('privado'); // privado | publico
            $table->date('vence_at')->nullable(); // garantías / vigencias documentales
            $table->foreignId('subido_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('documento_relacionado', function (Blueprint $table) {
            $table->foreignId('documento_id')->constrained('documentos')->cascadeOnDelete();
            $table->morphs('relacionado'); // relacionado_type, relacionado_id
            $table->string('rol', 40)->nullable(); // evidencia_antes | evidencia_durante | evidencia_despues | identificacion ...
            $table->timestamps();

            $table->unique(['documento_id', 'relacionado_type', 'relacionado_id', 'rol'], 'documento_relacionado_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_relacionado');
        Schema::dropIfExists('documentos');
    }
};
