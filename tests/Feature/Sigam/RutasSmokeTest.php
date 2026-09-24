<?php

namespace Tests\Feature\Sigam;

use App\Models\Usuario;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RutasSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite(); // las páginas Vue aún no existen; no resolver el manifest

        $this->seed([RolesPermisosSeeder::class, CatalogosSeeder::class]);

        $this->admin = Usuario::factory()->create();
        $this->admin->assignRole('superadministrador');
    }

    /**
     * @return list<array{0: string}>
     */
    public static function rutasGet(): array
    {
        return array_map(fn ($r) => [$r], [
            'dashboard',
            'sucursales.index', 'sucursales.create',
            'ubicaciones.index',
            'equipos.por_sucursal', 'equipos.create',
            'proveedores.index', 'proveedores.create',
            'normas.index', 'normas.create',
            'formatos.index', 'formatos.create',
            'catalogos.marcas.index',
            'catalogos.tipos_equipo.index',
            'catalogos.prioridades.index',
            'catalogos.estados_mantenimiento.index',
            'catalogos.materiales.index',
            'catalogos.tipos_area.index',
            'catalogos.tipos_limpieza.index',
            'solicitudes.index', 'solicitudes.create',
            'mantenimientos.index', 'mantenimientos.create',
            'planes.index', 'planes.create',
            'calendario.index',
            'tareas.index', 'tareas.create',
            'reportes.index',
            'auditoria.index',
            'notificaciones.index',
            'usuarios.index', 'usuarios.create',
            'roles.index',
        ]);
    }

    /** Cabeceras que hacen que Inertia responda JSON (sin renderizar Blade/Vite). */
    private const INERTIA = ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'];

    #[DataProvider('rutasGet')]
    public function test_ruta_get_responde_ok(string $nombre): void
    {
        $this->actingAs($this->admin)
            ->get(route($nombre))
            ->assertSuccessful();
    }

    public function test_reporte_inventario_general_se_genera(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.generar', 'inventario_general'))
            ->assertSuccessful();
    }

    public function test_usuario_sin_permiso_recibe_403(): void
    {
        $basico = Usuario::factory()->create();
        $basico->assignRole('usuario_basico');

        $this->actingAs($basico)
            ->get(route('usuarios.index'))
            ->assertForbidden();
    }
}
