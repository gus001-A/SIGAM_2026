<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Secuencias para generar folios consecutivos por año (SOL-2026-00001, MTO-2026-00001).
 * Ver App\Support\Folios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secuencias_folio', function (Blueprint $table) {
            $table->string('llave', 40)->primary(); // p.ej. "SOL-2026"
            $table->unsignedBigInteger('consecutivo')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencias_folio');
    }
};
