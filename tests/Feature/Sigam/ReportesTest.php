<?php

namespace Tests\Feature\Sigam;

use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Models\Sucursal;
use App\Models\TipoMantenimiento;
use App\Models\Usuario;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([RolesPermisosSeeder::class, CatalogosSeeder::class]);

        $this->admin = Usuario::factory()->create();
        $this->admin->assignRole('superadministrador');
    }

    public function test_index_agrupa_solo_reportes_disponibles(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reportes/Index')
                ->has('grupos', 3) // "Control" (solo auditoría, aún sin implementar) queda fuera
                ->has('grupos.0.reportes.0.clave')
                ->missing('grupos.0.reportes.0.disponible'));
    }

    /** @return list<array{0:string}> */
    public static function reportesImplementados(): array
    {
        return array_map(fn ($r) => [$r], [
            'inventario_general',
            'inventario_por_sucursal',
            'mantenimientos_por_periodo',
            'preventivos_proximos',
            'preventivos_vencidos',
            'correctivos',
            'urgencias',
            'productividad_tecnico',
            'documentos_activos',
            'solicitudes_por_usuario',
            'costos_mantenimiento',
        ]);
    }

    #[DataProvider('reportesImplementados')]
    public function test_reporte_se_genera_sin_pendiente(string $clave): void
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.generar', $clave))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reportes/Ver')
                ->where('clave', $clave)
                ->missing('pendiente')
                ->has('columnas')
                ->has('catalogos.sucursales'));
    }

    public function test_reporte_no_implementado_marca_pendiente(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.generar', 'indicadores_ejecutivos'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('pendiente', true));
    }

    public function test_inventario_por_sucursal_agrega_equipos_y_valor(): void
    {
        $s1 = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Norte', 'estado' => 'activo']);
        $s2 = Sucursal::create(['codigo' => 'S2', 'nombre' => 'Sur', 'estado' => 'activo']);
        Equipo::create(['codigo_activo' => 'EQ-1', 'descripcion' => 'A', 'sucursal_id' => $s1->id, 'valor_adquisicion' => 1000]);
        Equipo::create(['codigo_activo' => 'EQ-2', 'descripcion' => 'B', 'sucursal_id' => $s1->id, 'valor_adquisicion' => 500]);
        Equipo::create(['codigo_activo' => 'EQ-3', 'descripcion' => 'C', 'sucursal_id' => $s2->id, 'valor_adquisicion' => 300]);

        $this->actingAs($this->admin)
            ->get(route('reportes.generar', 'inventario_por_sucursal'))
            ->assertInertia(fn ($page) => $page
                ->where('totales.equipos', 3)
                ->where('totales.valor', 1800)
                ->where('filas.0.0', 'NORTE')
                ->where('filas.0.1', 2));
    }

    public function test_preventivos_proximos_respeta_la_ventana_de_dias(): void
    {
        $sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Matriz', 'estado' => 'activo']);
        $equipo = Equipo::create(['codigo_activo' => 'EQ-1', 'descripcion' => 'A', 'sucursal_id' => $sucursal->id]);
        $tipo = TipoMantenimiento::where('categoria', 'preventivo')->firstOrFail();

        PlanMantenimiento::create([
            'equipo_id' => $equipo->id, 'tipo_mantenimiento_id' => $tipo->id,
            'tipo_frecuencia' => 'mensual', 'valor_frecuencia' => 1,
            'proxima_fecha' => today()->addDays(10), 'dias_aviso_anticipado' => 7, 'estado' => 'activo',
        ]);
        PlanMantenimiento::create([
            'equipo_id' => $equipo->id, 'tipo_mantenimiento_id' => $tipo->id,
            'tipo_frecuencia' => 'mensual', 'valor_frecuencia' => 1,
            'proxima_fecha' => today()->addDays(90), 'dias_aviso_anticipado' => 7, 'estado' => 'activo',
        ]);

        $this->actingAs($this->admin)
            ->get(route('reportes.generar', 'preventivos_proximos', ['dias' => 30]))
            ->assertInertia(fn ($page) => $page
                ->where('totales.planes', 1)
                ->where('totales.ventana_dias', 30));
    }

    public function test_exportar_pdf_incluye_el_logo(): void
    {
        $pdf = $this->actingAs($this->admin)
            ->get(route('reportes.exportar', ['clave' => 'inventario_general', 'formato' => 'pdf']))
            ->assertOk()
            ->getContent();

        // El PDF debe incrustar al menos un XObject de imagen (el logo).
        $this->assertStringContainsString('/Subtype /Image', $pdf);
    }

    public function test_exportar_xlsx_y_pdf(): void
    {
        $xlsx = $this->actingAs($this->admin)
            ->get(route('reportes.exportar', ['clave' => 'inventario_general', 'formato' => 'xlsx']))
            ->assertOk();
        $this->assertStringContainsString('spreadsheetml', $xlsx->headers->get('content-type'));

        $pdf = $this->actingAs($this->admin)
            ->get(route('reportes.exportar', ['clave' => 'inventario_general', 'formato' => 'pdf']))
            ->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
    }

    public function test_exportar_formato_no_soportado_es_422(): void
    {
        $this->actingAs($this->admin)
            ->get(route('reportes.exportar', ['clave' => 'inventario_general', 'formato' => 'xml']))
            ->assertStatus(422);
    }

    public function test_usuario_sin_rol_no_ve_reportes(): void
    {
        $sinRol = Usuario::factory()->create();

        $this->actingAs($sinRol)->get(route('reportes.index'))->assertForbidden();
    }

    public function test_tecnico_no_puede_exportar(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');

        $this->actingAs($tecnico)
            ->get(route('reportes.exportar', ['clave' => 'inventario_general', 'formato' => 'pdf']))
            ->assertForbidden();
    }
}
