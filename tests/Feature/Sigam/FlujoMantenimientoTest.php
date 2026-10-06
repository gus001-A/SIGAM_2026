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
use Illuminate\Http\UploadedFile;
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
        $this->actingAs($tecnico)->post(route('mantenimientos.transicion', $orden), [
            'estado' => 'en_proceso',
            'nota' => 'Inicio el trabajo.',
            'evidencias' => [UploadedFile::fake()->image('inicio.jpg')],
        ])->assertRedirect();
        $this->actingAs($tecnico)->patch(route('mantenimientos.update', $orden), [
            'diagnostico' => 'Capacitor dañado.',
            'descripcion_trabajo' => 'Reemplazo de capacitor y prueba.',
        ])->assertRedirect();
        $this->actingAs($tecnico)->post(route('mantenimientos.transicion', $orden), [
            'estado' => 'realizado',
            'nota' => 'Capacitor reemplazado y probado.',
            'evidencias' => [UploadedFile::fake()->image('realizado.jpg')],
        ])->assertRedirect();

        // 5. No se puede cerrar sin pasar por supervisión.
        $this->assertFalse(CicloMantenimiento::permite('realizado', 'cerrado'));

        // 6. El supervisor supervisa y cierra.
        $this->actingAs($supervisor)->post(route('mantenimientos.transicion', $orden), [
            'estado' => 'supervisado',
            'nota' => 'Trabajo verificado en sitio.',
            'evidencias' => [UploadedFile::fake()->image('supervisado.jpg')],
        ])->assertRedirect();
        $this->actingAs($supervisor)->post(route('mantenimientos.transicion', $orden), [
            'estado' => 'cerrado',
            'nota' => 'Orden cerrada conforme.',
            'evidencias' => [UploadedFile::fake()->image('cerrado.jpg')],
        ])->assertRedirect();

        $orden->refresh();
        $this->assertSame('cerrado', $orden->estado->clave);
        $this->assertNotNull($orden->cerrado_at);
        $this->assertSame($supervisor->id, $orden->supervisor_id);
        // autorizado, asignado, en_proceso, realizado, supervisado, cerrado
        $this->assertSame(6, $orden->historialEstados()->count());
        // una evidencia por cada transición hecha vía transicion() (4: en_proceso, realizado, supervisado, cerrado)
        $this->assertSame(4, $orden->documentos()->wherePivot('rol', 'evidencia')->count());
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
            ->post(route('mantenimientos.transicion', $orden), [
                'estado' => 'cerrado',
                'nota' => 'Intento de cierre.',
                'evidencias' => [UploadedFile::fake()->image('cierre.jpg')],
            ])
            ->assertRedirect();

        $this->assertSame('supervisado', $orden->refresh()->estado->clave);
    }

    public function test_cerrar_orden_no_exige_foto(): void
    {
        $sucursal = Sucursal::create(['codigo' => 'S3', 'nombre' => 'Sucursal 3', 'estado' => 'activo']);
        $equipo = Equipo::create(['codigo_activo' => 'EQ-3', 'descripcion' => 'Motor', 'sucursal_id' => $sucursal->id]);
        $admin = Usuario::factory()->create();
        $admin->assignRole('superadministrador');

        $orden = Mantenimiento::create([
            'folio' => 'MTO-TEST-2',
            'equipo_id' => $equipo->id,
            'sucursal_id' => $sucursal->id,
            'tipo_id' => TipoMantenimiento::where('clave', 'correctivo')->value('id'),
            'prioridad_id' => Prioridad::where('clave', 'normal')->value('id'),
            'estado_id' => CicloMantenimiento::estado('supervisado')->id,
            'diagnostico' => 'Falla identificada.',
            'descripcion_trabajo' => 'Reparación realizada.',
            'completado_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('mantenimientos.transicion', $orden), ['estado' => 'cerrado', 'nota' => 'Cerrada sin foto.'])
            ->assertRedirect();

        $this->assertSame('cerrado', $orden->refresh()->estado->clave);
    }

    public function test_dias_de_retraso_de_una_orden_completada_fuera_de_fecha(): void
    {
        $sucursal = Sucursal::create(['codigo' => 'S4', 'nombre' => 'Sucursal 4', 'estado' => 'activo']);
        $equipo = Equipo::create(['codigo_activo' => 'EQ-4', 'descripcion' => 'Compresor', 'sucursal_id' => $sucursal->id]);
        $admin = Usuario::factory()->create();
        $admin->assignRole('superadministrador');

        $orden = Mantenimiento::create([
            'folio' => 'MTO-TEST-3',
            'equipo_id' => $equipo->id,
            'sucursal_id' => $sucursal->id,
            'tipo_id' => TipoMantenimiento::where('clave', 'correctivo')->value('id'),
            'prioridad_id' => Prioridad::where('clave', 'normal')->value('id'),
            'estado_id' => CicloMantenimiento::estado('en_proceso')->id,
            'programado_inicio' => now()->subDays(5),
            'programado_fin' => now()->subDays(4),
        ]);

        $this->assertNull($orden->diasRetraso());

        $orden->forceFill(['completado_at' => now()])->save();

        $this->assertSame(4, $orden->refresh()->diasRetraso());

        $this->actingAs($admin)
            ->get(route('mantenimientos.show', $orden))
            ->assertInertia(fn ($p) => $p->where('retrasoDias', 4));
    }
}
