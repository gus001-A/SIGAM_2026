<?php

namespace Tests\Feature\Sigam;

use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Prioridad;
use App\Models\SolicitudMantenimiento;
use App\Models\Sucursal;
use App\Models\TipoMantenimiento;
use App\Models\Usuario;
use App\Support\CicloMantenimiento;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SolicitudesOrdenesTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $supervisor;

    private Equipo $equipo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([RolesPermisosSeeder::class, CatalogosSeeder::class]);

        $sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Matriz', 'estado' => 'activo']);
        $this->equipo = Equipo::create(['codigo_activo' => 'EQ-1', 'descripcion' => 'Bomba', 'sucursal_id' => $sucursal->id]);

        $this->supervisor = Usuario::factory()->create(['sucursal_id' => $sucursal->id]);
        $this->supervisor->assignRole('supervisor');
    }

    public function test_listado_de_solicitudes_filtra_por_folio_y_pagina_15(): void
    {
        $estado = CicloMantenimiento::estado('solicitado');
        foreach (range(1, 20) as $i) {
            SolicitudMantenimiento::create([
                'folio' => sprintf('SOL-2026-%05d', $i),
                'equipo_id' => $this->equipo->id,
                'sucursal_id' => $this->equipo->sucursal_id,
                'solicitado_por' => $this->supervisor->id,
                'estado_id' => $estado->id,
                'descripcion' => "Falla {$i}",
                'solicitado_at' => now(),
            ]);
        }

        $this->actingAs($this->supervisor)
            ->get(route('solicitudes.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('solicitudes.per_page', 15)->where('solicitudes.total', 20));

        $this->actingAs($this->supervisor)
            ->get(route('solicitudes.index', ['folio' => '00007']))
            ->assertInertia(fn ($p) => $p->where('solicitudes.total', 1)
                ->where('solicitudes.data.0.folio', 'SOL-2026-00007'));
    }

    public function test_usuario_basico_solo_ve_sus_solicitudes(): void
    {
        $otro = Usuario::factory()->create();
        $otro->assignRole('usuario_basico');
        $basico = Usuario::factory()->create();
        $basico->assignRole('usuario_basico');

        $estado = CicloMantenimiento::estado('solicitado');
        SolicitudMantenimiento::create(['folio' => 'A', 'equipo_id' => $this->equipo->id, 'sucursal_id' => $this->equipo->sucursal_id, 'solicitado_por' => $otro->id, 'estado_id' => $estado->id, 'descripcion' => 'x', 'solicitado_at' => now()]);
        SolicitudMantenimiento::create(['folio' => 'B', 'equipo_id' => $this->equipo->id, 'sucursal_id' => $this->equipo->sucursal_id, 'solicitado_por' => $basico->id, 'estado_id' => $estado->id, 'descripcion' => 'y', 'solicitado_at' => now()]);

        $this->actingAs($basico)
            ->get(route('solicitudes.index'))
            ->assertInertia(fn ($p) => $p->where('solicitudes.total', 1)->where('solicitudes.data.0.folio', 'B'));
    }

    public function test_autorizar_solicitud_crea_orden_y_marca_revisada(): void
    {
        $solicitud = SolicitudMantenimiento::create([
            'folio' => 'SOL-X', 'equipo_id' => $this->equipo->id, 'sucursal_id' => $this->equipo->sucursal_id,
            'solicitado_por' => $this->supervisor->id, 'estado_id' => CicloMantenimiento::estado('solicitado')->id,
            'descripcion' => 'No enciende', 'solicitado_at' => now(),
        ]);

        $this->actingAs($this->supervisor)
            ->post(route('solicitudes.autorizar', $solicitud), [
                'tipo_id' => TipoMantenimiento::where('clave', 'correctivo')->value('id'),
                'prioridad_id' => Prioridad::where('clave', 'normal')->value('id'),
            ])
            ->assertRedirect();

        $orden = Mantenimiento::firstOrFail();
        $this->assertSame($solicitud->id, $orden->solicitud_id);
        $this->assertSame('NO ENCIENDE', $orden->problema_reportado);
        $this->assertSame('autorizado', $orden->estado->clave);
        $this->assertNotNull($solicitud->fresh()->revisado_at);
    }

    public function test_no_se_puede_autorizar_dos_veces(): void
    {
        $solicitud = SolicitudMantenimiento::create([
            'folio' => 'SOL-Y', 'equipo_id' => $this->equipo->id, 'sucursal_id' => $this->equipo->sucursal_id,
            'solicitado_por' => $this->supervisor->id, 'estado_id' => CicloMantenimiento::estado('solicitado')->id,
            'descripcion' => 'x', 'solicitado_at' => now(),
        ]);
        $datos = [
            'tipo_id' => TipoMantenimiento::where('clave', 'correctivo')->value('id'),
            'prioridad_id' => Prioridad::where('clave', 'normal')->value('id'),
        ];

        $this->actingAs($this->supervisor)->post(route('solicitudes.autorizar', $solicitud), $datos);
        $this->actingAs($this->supervisor)->post(route('solicitudes.autorizar', $solicitud), $datos)
            ->assertSessionHas('error');

        $this->assertSame(1, Mantenimiento::count());
    }

    public function test_listado_de_ordenes_filtra_por_estado(): void
    {
        $tipo = TipoMantenimiento::where('clave', 'correctivo')->value('id');
        $prio = Prioridad::where('clave', 'normal')->value('id');
        $abierta = CicloMantenimiento::estado('en_proceso');
        $cerrada = CicloMantenimiento::estado('cerrado');

        Mantenimiento::create(['folio' => 'M1', 'equipo_id' => $this->equipo->id, 'sucursal_id' => $this->equipo->sucursal_id, 'tipo_id' => $tipo, 'prioridad_id' => $prio, 'estado_id' => $abierta->id]);
        Mantenimiento::create(['folio' => 'M2', 'equipo_id' => $this->equipo->id, 'sucursal_id' => $this->equipo->sucursal_id, 'tipo_id' => $tipo, 'prioridad_id' => $prio, 'estado_id' => $cerrada->id]);

        $this->actingAs($this->supervisor)
            ->get(route('mantenimientos.index', ['estado_id' => $cerrada->id]))
            ->assertInertia(fn ($p) => $p->where('mantenimientos.total', 1)->where('mantenimientos.data.0.folio', 'M2'));
    }

    public function test_tecnico_solo_ve_ordenes_asignadas(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');
        $tipo = TipoMantenimiento::where('clave', 'correctivo')->value('id');
        $prio = Prioridad::where('clave', 'normal')->value('id');
        $estado = CicloMantenimiento::estado('asignado');

        $suya = Mantenimiento::create(['folio' => 'MT-1', 'equipo_id' => $this->equipo->id, 'sucursal_id' => $this->equipo->sucursal_id, 'tipo_id' => $tipo, 'prioridad_id' => $prio, 'estado_id' => $estado->id]);
        Mantenimiento::create(['folio' => 'MT-2', 'equipo_id' => $this->equipo->id, 'sucursal_id' => $this->equipo->sucursal_id, 'tipo_id' => $tipo, 'prioridad_id' => $prio, 'estado_id' => $estado->id]);
        $suya->asignaciones()->create(['tecnico_id' => $tecnico->id, 'asignado_at' => now()]);

        $this->actingAs($tecnico)
            ->get(route('mantenimientos.index'))
            ->assertInertia(fn ($p) => $p->where('mantenimientos.total', 1)->where('mantenimientos.data.0.folio', 'MT-1'));
    }
}
