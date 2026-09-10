<?php

namespace Tests\Feature\Sigam;

use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\PlanMantenimiento;
use App\Models\Sucursal;
use App\Models\TipoMantenimiento;
use App\Models\Usuario;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanesPreventivosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Equipo $equipo;

    private TipoMantenimiento $tipoPreventivo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([RolesPermisosSeeder::class, CatalogosSeeder::class]);

        $this->admin = Usuario::factory()->create();
        $this->admin->assignRole('superadministrador');

        $sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Matriz', 'estado' => 'activo']);
        $this->equipo = Equipo::create(['codigo_activo' => 'EQ-001', 'descripcion' => 'Compresor', 'sucursal_id' => $sucursal->id]);
        $this->tipoPreventivo = TipoMantenimiento::where('categoria', 'preventivo')->firstOrFail();
    }

    public function test_crea_plan_y_calcula_proxima_fecha(): void
    {
        $this->actingAs($this->admin)
            ->post(route('planes.store'), [
                'equipo_id' => $this->equipo->id,
                'tipo_mantenimiento_id' => $this->tipoPreventivo->id,
                'nombre' => 'Preventivo mensual',
                'tipo_frecuencia' => 'mensual',
                'valor_frecuencia' => 1,
                'fecha_inicio' => '2026-01-01',
                'dias_aviso_anticipado' => 7,
                'estado' => 'activo',
            ])
            ->assertRedirect();

        $plan = PlanMantenimiento::firstOrFail();
        $this->assertSame('2026-02-01', $plan->proxima_fecha->toDateString());
    }

    public function test_genera_ocurrencias_y_luego_una_orden(): void
    {
        $plan = PlanMantenimiento::create([
            'equipo_id' => $this->equipo->id,
            'tipo_mantenimiento_id' => $this->tipoPreventivo->id,
            'tipo_frecuencia' => 'mensual',
            'valor_frecuencia' => 1,
            'fecha_inicio' => '2026-01-01',
            'proxima_fecha' => '2026-02-01',
            'dias_aviso_anticipado' => 7,
            'estado' => 'activo',
        ]);

        $this->actingAs($this->admin)
            ->post(route('planes.ocurrencias.generar', $plan), ['cantidad' => 3])
            ->assertRedirect();

        $this->assertSame(3, $plan->ocurrencias()->count());

        $ocurrencia = $plan->ocurrencias()->orderBy('fecha_programada')->first();

        $this->actingAs($this->admin)
            ->post(route('planes.orden.generar', $plan), ['ocurrencia_id' => $ocurrencia->id])
            ->assertRedirect();

        $ocurrencia->refresh();
        $this->assertNotNull($ocurrencia->mantenimiento_id);
        $this->assertSame('generada', $ocurrencia->estado);
        $this->assertSame(1, Mantenimiento::where('plan_id', $plan->id)->count());
    }

    public function test_listado_filtra_por_vencidos_y_pagina_15(): void
    {
        PlanMantenimiento::create([
            'equipo_id' => $this->equipo->id, 'tipo_mantenimiento_id' => $this->tipoPreventivo->id,
            'tipo_frecuencia' => 'mensual', 'valor_frecuencia' => 1,
            'proxima_fecha' => today()->subMonth(), 'dias_aviso_anticipado' => 7, 'estado' => 'activo',
        ]);
        PlanMantenimiento::create([
            'equipo_id' => $this->equipo->id, 'tipo_mantenimiento_id' => $this->tipoPreventivo->id,
            'tipo_frecuencia' => 'mensual', 'valor_frecuencia' => 1,
            'proxima_fecha' => today()->addMonth(), 'dias_aviso_anticipado' => 7, 'estado' => 'activo',
        ]);

        $this->actingAs($this->admin)
            ->get(route('planes.index', ['vencidos' => 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('planes.total', 1)
                ->where('planes.per_page', 15)
                ->where('planes.data.0.vencido', true));
    }
}
