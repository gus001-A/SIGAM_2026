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

class FlujoMantenimientoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([RolesPermisosSeeder::class, CatalogosSeeder::class]);
    }

    public function test_ciclo_completo_correctivo(): void
    {
        $sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Sucursal 1', 'estado' => 'activo']);
        $equipo = Equipo::create(['codigo_activo' => 'EQ-1', 'descripcion' => 'Bomba', 'sucursal_id' => $sucursal->id]);

        $supervisor = Usuario::factory()->create(['sucursal_id' => $sucursal->id]);
        $supervisor->assignRole('supervisor');
        $tecnico = Usuario::factory()->create(['sucursal_id' => $sucursal->id]);
        $tecnico->assignRole('tecnico');
        $solicitante = Usuario::factory()->create(['sucursal_id' => $sucursal->id]);
        $solicitante->assignRole('usuario_basico');

        // 1. El usuario básico crea una solicitud.
        $this->actingAs($solicitante)->post(route('solicitudes.store'), [
            'equipo_id' => $equipo->id,
            'descripcion' => 'La bomba no enciende.',
        ])->assertRedirect();

        $solicitud = SolicitudMantenimiento::firstOrFail();
        $this->assertStringStartsWith('SOL-', $solicitud->folio);

        // 2. El supervisor la autoriza -> se crea la orden.
        $this->actingAs($supervisor)->post(route('solicitudes.autorizar', $solicitud), [
            'tipo_id' => TipoMantenimiento::where('clave', 'correctivo')->value('id'),
            'prioridad_id' => Prioridad::where('clave', 'urgente')->value('id'),
        ])->assertRedirect();

        $orden = Mantenimiento::firstOrFail();
        $this->assertStringStartsWith('MTO-', $orden->folio);
        $this->assertSame('autorizado', $orden->estado->clave);

        // 3. Asignar técnico -> pasa a "asignado".
        $this->actingAs($supervisor)->post(route('mantenimientos.asignaciones.store', $orden), [
            'tecnico_id' => $tecnico->id,
            'es_principal' => true,
        ])->assertRedirect();
        $this->assertSame('asignado', $orden->refresh()->estado->clave);

        // 4. El técnico inicia y documenta.
        $this->actingAs($tecnico)->post(route('mantenimientos.transicion', $orden), ['estado' => 'en_proceso'])->assertRedirect();
        $this->actingAs($tecnico)->patch(route('mantenimientos.update', $orden), [
            'diagnostico' => 'Capacitor dañado.',
            'descripcion_trabajo' => 'Reemplazo de capacitor y prueba.',
        ])->assertRedirect();
        $this->actingAs($tecnico)->post(route('mantenimientos.transicion', $orden), ['estado' => 'realizado'])->assertRedirect();

        // 5. No se puede cerrar sin pasar por supervisión.
        $this->assertFalse(CicloMantenimiento::permite('realizado', 'cerrado'));

        // 6. El supervisor supervisa y cierra.
        $this->actingAs($supervisor)->post(route('mantenimientos.transicion', $orden), ['estado' => 'supervisado'])->assertRedirect();
        $this->actingAs($supervisor)->post(route('mantenimientos.transicion', $orden), ['estado' => 'cerrado'])->assertRedirect();

        $orden->refresh();
        $this->assertSame('cerrado', $orden->estado->clave);
        $this->assertNotNull($orden->cerrado_at);
        $this->assertSame($supervisor->id, $orden->supervisor_id);
        // autorizado, asignado, en_proceso, realizado, supervisado, cerrado
        $this->assertSame(6, $orden->historialEstados()->count());
    }

    public function test_no_cierra_sin_campos_minimos(): void
    {
        $sucursal = Sucursal::create(['codigo' => 'S2', 'nombre' => 'Sucursal 2', 'estado' => 'activo']);
        $equipo = Equipo::create(['codigo_activo' => 'EQ-2', 'descripcion' => 'Motor', 'sucursal_id' => $sucursal->id]);
        $admin = Usuario::factory()->create();
        $admin->assignRole('superadministrador');

        $orden = Mantenimiento::create([
            'folio' => 'MTO-TEST-1',
            'equipo_id' => $equipo->id,
            'sucursal_id' => $sucursal->id,
            'tipo_id' => TipoMantenimiento::where('clave', 'correctivo')->value('id'),
            'prioridad_id' => Prioridad::where('clave', 'normal')->value('id'),
            'estado_id' => CicloMantenimiento::estado('supervisado')->id,
        ]);

        $this->actingAs($admin)
            ->post(route('mantenimientos.transicion', $orden), ['estado' => 'cerrado'])
            ->assertRedirect();

        $this->assertSame('supervisado', $orden->refresh()->estado->clave);
    }
}
