<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Título corto de la tarea (antes solo tenía descripción larga) y quién la
 * registró (para saber de quién es "propia" al filtrar por permisos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->string('titulo', 150)->nullable()->after('id');
            $table->foreignId('creado_por')->nullable()->after('cancelada_at')->constrained('usuarios')->nullOnDelete();
        });

        // Rellena el título de las tareas que ya existan a partir de su
        // descripción, y el creador a partir del primer registro de
        // auditoría "crear" (EsAuditable ya lo guarda ahí).
        DB::table('tareas')->whereNull('titulo')->orderBy('id')->get(['id', 'descripcion'])->each(function (object $t) {
            DB::table('tareas')->where('id', $t->id)->update([
                'titulo' => Str::limit(trim((string) $t->descripcion), 100, ''),
            ]);
        });

        // Portable entre motores (MySQL en producción, SQLite en pruebas): se
        // arma en PHP en vez de un UPDATE...JOIN que solo entiende MySQL.
        DB::table('registros_auditoria')
            ->where('auditable_type', 'App\\Models\\Tarea')
            ->where('accion', 'crear')
            ->orderBy('id')
            ->get(['auditable_id', 'usuario_id'])
            ->unique('auditable_id')
            ->each(function (object $registro) {
                DB::table('tareas')
                    ->where('id', $registro->auditable_id)
                    ->whereNull('creado_por')
                    ->update(['creado_por' => $registro->usuario_id]);
            });

        Schema::table('tareas', function (Blueprint $table) {
            $table->string('titulo', 150)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tareas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creado_por');
            $table->dropColumn('titulo');
        });
    }
};
