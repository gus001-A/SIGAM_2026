<?php

namespace Tests\Feature\Sigam;

use App\Models\Sucursal;
use App\Models\Usuario;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UsuariosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesPermisosSeeder::class);

        $this->admin = Usuario::factory()->create(['nombre' => 'Admin', 'apellidos' => 'Root']);
        $this->admin->assignRole('superadministrador');
    }

    public function test_crea_usuario_con_roles_y_hashea_contrasena(): void
    {
        $sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Matriz', 'estado' => 'activo']);

        $this->actingAs($this->admin)
            ->post(route('usuarios.store'), [
                'nombre' => 'Laura',
                'apellidos' => 'Méndez',
                'email' => 'laura@sigam.test',
                'sucursal_id' => $sucursal->id,
                'estado' => 'activo',
                'password' => 'Secreta-123',
                'password_confirmation' => 'Secreta-123',
                'roles' => ['tecnico'],
            ])
            ->assertRedirect();

        $usuario = Usuario::where('email', 'laura@sigam.test')->firstOrFail();
        $this->assertTrue($usuario->hasRole('tecnico'));
        $this->assertTrue(Hash::check('Secreta-123', $usuario->password));
    }

    public function test_el_usuario_debe_tener_exactamente_un_rol(): void
    {
        $base = [
            'nombre' => 'Ana', 'email' => 'ana@sigam.test', 'estado' => 'activo',
            'password' => 'Secreta-123', 'password_confirmation' => 'Secreta-123',
        ];

        // Sin rol → error.
        $this->actingAs($this->admin)->from(route('usuarios.create'))
            ->post(route('usuarios.store'), $base)
            ->assertSessionHasErrors('roles');

        // Dos roles → error.
        $this->actingAs($this->admin)->from(route('usuarios.create'))
            ->post(route('usuarios.store'), [...$base, 'roles' => ['tecnico', 'supervisor']])
            ->assertSessionHasErrors('roles');

        // Un rol → ok.
        $this->actingAs($this->admin)
            ->post(route('usuarios.store'), [...$base, 'roles' => ['tecnico']])
            ->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(['tecnico'], Usuario::where('email', 'ana@sigam.test')->first()->getRoleNames()->all());
    }

    public function test_email_duplicado_es_rechazado(): void
    {
        Usuario::factory()->create(['email' => 'dup@sigam.test']);

        $this->actingAs($this->admin)
            ->from(route('usuarios.create'))
            ->post(route('usuarios.store'), [
                'nombre' => 'X', 'email' => 'dup@sigam.test', 'estado' => 'activo',
                'password' => 'Secreta-123', 'password_confirmation' => 'Secreta-123',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_editar_sin_contrasena_conserva_la_actual(): void
    {
        $usuario = Usuario::factory()->create(['password' => Hash::make('vieja-clave-123')]);
        $usuario->assignRole('usuario_basico');

        $this->actingAs($this->admin)
            ->put(route('usuarios.update', $usuario), [
                'nombre' => 'Nuevo Nombre',
                'email' => $usuario->email,
                'estado' => 'activo',
                'password' => '',
                'password_confirmation' => '',
                'roles' => ['supervisor'],
            ])
            ->assertRedirect();

        $usuario->refresh();
        $this->assertSame('Nuevo Nombre', $usuario->nombre);
        $this->assertTrue(Hash::check('vieja-clave-123', $usuario->password));
        $this->assertTrue($usuario->hasRole('supervisor'));
        $this->assertFalse($usuario->hasRole('usuario_basico'));
    }

    public function test_baja_logica_y_reactivacion(): void
    {
        $usuario = Usuario::factory()->create();

        $this->actingAs($this->admin)->delete(route('usuarios.destroy', $usuario))->assertRedirect();
        $this->assertSoftDeleted($usuario);

        $this->actingAs($this->admin)->put(route('usuarios.restore', $usuario->id))->assertRedirect();
        $usuario->refresh();
        $this->assertNull($usuario->deleted_at);
        $this->assertSame('activo', $usuario->estado);
    }

    public function test_no_puede_desactivar_su_propia_cuenta(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('usuarios.destroy', $this->admin))
            ->assertForbidden();
    }

    public function test_listado_filtra_por_columna_y_rol(): void
    {
        $tecnico = Usuario::factory()->create(['nombre' => 'Pedro', 'apellidos' => 'Ruiz']);
        $tecnico->assignRole('tecnico');
        $otro = Usuario::factory()->create(['nombre' => 'Sofía', 'apellidos' => 'Luna']);
        $otro->assignRole('supervisor');

        $this->actingAs($this->admin)
            ->get(route('usuarios.index', ['rol' => 'tecnico']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('usuarios.total', 1)
                ->where('usuarios.per_page', 15)
                ->where('usuarios.data.0.nombre_completo', 'Pedro Ruiz'));
    }

    public function test_usuarios_inactivos_se_listan_al_filtrar_por_estado(): void
    {
        $usuario = Usuario::factory()->create(['nombre' => 'Baja']);
        $this->actingAs($this->admin)->delete(route('usuarios.destroy', $usuario));

        $this->actingAs($this->admin)
            ->get(route('usuarios.index', ['estado' => 'inactivo']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('usuarios.total', 1));
    }

    public function test_tecnico_no_puede_administrar_usuarios(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');

        $this->actingAs($tecnico)->get(route('usuarios.index'))->assertForbidden();
        $this->actingAs($tecnico)
            ->post(route('usuarios.store'), ['nombre' => 'x', 'email' => 'x@x.test', 'estado' => 'activo', 'password' => 'Secreta-123', 'password_confirmation' => 'Secreta-123'])
            ->assertForbidden();
    }
}
