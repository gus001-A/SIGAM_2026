<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Un movimiento (avance de una tarea o de una orden) puede llevar varias
 * fotos de evidencia. Se reemplaza el enlace único por una tabla pivote
 * polimórfica; los enlaces únicos anteriores se copian tal cual, sin
 * heurísticas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidencias_movimiento', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('movimiento_type', 120);
            $table->unsignedBigInteger('movimiento_id');
            $table->foreignId('documento_id')->constrained('documentos')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['movimiento_type', 'movimiento_id']);
            $table->unique(['movimiento_type', 'movimiento_id', 'documento_id'], 'evidencias_movimiento_unico');
        });

        $this->copiarEnlaces('historial_estados_tarea', 'App\\Models\\HistorialEstadoTarea');
        $this->copiarEnlaces('observaciones_mantenimiento', 'App\\Models\\ObservacionMantenimiento');
    }

    private function copiarEnlaces(string $tabla, string $tipo): void
    {
        $ahora = now();

        DB::table($tabla)->whereNotNull('documento_evidencia_id')
            ->get(['id', 'documento_evidencia_id'])
            ->each(fn ($fila) => DB::table('evidencias_movimiento')->insert([
                'movimiento_type' => $tipo,
                'movimiento_id' => $fila->id,
                'documento_id' => $fila->documento_evidencia_id,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias_movimiento');
    }
};
