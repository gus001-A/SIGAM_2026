<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla pivote usuario ↔ sucursal (un usuario puede tener varias sucursales).
 * El modelo Usuario::sucursales() la usa desde hace tiempo, pero nunca tuvo
 * migración. Se copia la sucursal actual de cada usuario (usuarios.sucursal_id)
 * para que nadie pierda su acceso al aplicar esta migración.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursal_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['usuario_id', 'sucursal_id']);
        });

        DB::table('usuarios')
            ->whereNotNull('sucursal_id')
            ->orderBy('id')
            ->get(['id', 'sucursal_id'])
            ->each(fn ($u) => DB::table('sucursal_usuario')->insertOrIgnore([
                'usuario_id' => $u->id,
                'sucursal_id' => $u->sucursal_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursal_usuario');
    }
};
