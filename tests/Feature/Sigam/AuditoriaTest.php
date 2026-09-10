<?php

namespace Tests\Feature\Sigam;

use App\Models\RegistroAuditoria;
use App\Models\Sucursal;
use App\Models\Usuario;
use App\Support\Auditoria;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesPermisosSeeder::class);

        $this->admin = Usuario::factory()->create(['nombre' => 'Ana', 'apellidos' => 'Torres']);
        $this->admin->assignRole('superadministrador');
    }

    public function test_crear_un_modelo_auditable_registra_evento(): void
    {
        $this->actingAs($this->admin);

        $sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Central', 'estado' => 'activo']);

        $this->assertDatabaseHas('registros_auditoria', [
            'usuario_id' => $this->admin->id,
            'accion' => 'crear',
            'modulo' => 'sucursales',
            'auditable_id' => $sucursal->id,
        ]);
    }

    public function test_actualizar_registra_el_diff(): void
    {
        $this->actingAs($this->admin);
        $sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Central', 'estado' => 'activo']);

        RegistroAuditoria::query()->delete();
        $sucursal->update(['nombre' => 'Central Renombrada']);

        $registro = RegistroAuditoria::where('accion', 'actualizar')->firstOrFail();
        $this->assertSame('Central', $registro->valores_anteriores['nombre']);
        $this->assertSame('Central Renombrada', $registro->valores_nuevos['nombre']);
    }

    public function test_no_audita_sin_usuario_autenticado(): void
    {
        Sucursal::create(['codigo' => 'S1', 'nombre' => 'Sin sesión', 'estado' => 'activo']);

        $this->assertDatabaseCount('registros_auditoria', 0);
    }

    public function test_no_guarda_campos_sensibles(): void
    {
        $this->actingAs($this->admin);

        $usuario = Usuario::create([
            'nombre' => 'Nuevo', 'email' => 'nuevo@sigam.test', 'password' => bcrypt('Secreta-123'), 'estado' => 'activo',
        ]);

        $registro = RegistroAuditoria::where('auditable_id', $usuario->id)->where('accion', 'crear')->firstOrFail();
        $this->assertArrayNotHasKey('password', $registro->valores_nuevos);
    }

    public function test_login_registra_acceso_y_actualiza_ultimo_acceso(): void
    {
        $this->post(route('login'), ['email' => $this->admin->email, 'password' => 'password']);

        // El factory usa 'password' como contraseña por defecto.
        $this->assertDatabaseHas('registros_auditoria', ['usuario_id' => $this->admin->id, 'accion' => 'acceso', 'modulo' => 'autenticacion']);
        $this->assertNotNull($this->admin->fresh()->ultimo_acceso_at);
    }

    public function test_index_filtra_por_modulo_y_accion(): void
    {
        Auditoria::registrar('crear', 'equipos', usuario: $this->admin);
        Auditoria::registrar('actualizar', 'equipos', usuario: $this->admin);
        Auditoria::registrar('crear', 'normas', usuario: $this->admin);

        $this->actingAs($this->admin)
            ->get(route('auditoria.index', ['modulo' => 'equipos', 'accion' => 'crear']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('registros.total', 1)
                ->where('registros.per_page', 15)
                ->where('registros.data.0.modulo', 'equipos'));
    }

    public function test_listado_incluye_el_diff_para_el_detalle_expandible(): void
    {
        Auditoria::registrar('actualizar', 'sucursales', null, ['nombre' => 'A'], ['nombre' => 'B'], [], $this->admin);

        $this->actingAs($this->admin)
            ->get(route('auditoria.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auditoria/Index')
                ->where('registros.data.0.valores_anteriores.nombre', 'A')
                ->where('registros.data.0.valores_nuevos.nombre', 'B'));
    }

    public function test_exporta_csv(): void
    {
        Auditoria::registrar('crear', 'equipos', usuario: $this->admin);

        $respuesta = $this->actingAs($this->admin)->get(route('auditoria.exportar'));
        $respuesta->assertOk();
        $this->assertStringContainsString('text/csv', $respuesta->headers->get('content-type'));
    }

    public function test_tecnico_no_puede_ver_ni_exportar_auditoria(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');

        $this->actingAs($tecnico)->get(route('auditoria.index'))->assertForbidden();
        $this->actingAs($tecnico)->get(route('auditoria.exportar'))->assertForbidden();
    }
}
