<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /** La raíz del sitio manda directo al inicio de sesión para un visitante anónimo. */
    public function test_la_raiz_redirige_al_inicio_de_sesion(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    /** Con sesión activa, la raíz manda directo al dashboard. */
    public function test_la_raiz_redirige_al_dashboard_si_ya_hay_sesion(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        $usuario = Usuario::factory()->create();
        $usuario->assignRole('superadministrador');

        $response = $this->actingAs($usuario)->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
