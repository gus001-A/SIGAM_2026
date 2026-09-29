<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Por decisión explícita del negocio, el correo ya no es único: dos usuarios
 * pueden compartir el mismo correo (y el mismo teléfono, que nunca tuvo esta
 * restricción). `LoginRequest::authenticate()` se ajustó para probar la
 * contraseña contra CADA fila que coincida con ese correo (en vez de
 * `Auth::attempt`, que solo revisa la primera), así que el login sigue
 * funcionando aunque haya correos repetidos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
