<?php

namespace Tests\Feature\Sigam;

use App\Models\Equipo;
use App\Models\Mantenimiento;
use App\Models\Notificacion;
use App\Models\PlanMantenimiento;
use App\Models\Prioridad;
use App\Models\Sucursal;
use App\Models\TipoMantenimiento;
use App\Models\Usuario;
use App\Support\CicloMantenimiento;
use App\Support\Notificaciones;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificacionesTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Usuario $tecnico;

    private Equipo $equipo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([RolesPermisosSeeder::class, CatalogosSeeder::class]);

        $this->admin = Usuario::factory()->create(['nombre' => 'Sofía']);
        $this->admin->assignRole('superadministrador');

        $this->tecnico = Usuario::factory()->create(['nombre' => 'Beto']);
        $this->tecnico->assignRole('tecnico');

        $sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Matriz', 'estado' => 'activo']);
        $this->equipo = Equipo::create(['codigo_activo' => 'EQ-1', 'descripcion' => 'Compresor', 'sucursal_id' => $sucursal->id]);
    }

    private function nuevaOrden(?string $prioridadClave = null): Mantenimiento
    {
        $sucursal = $this->equipo->sucursal_id;
        $prioridad = $prioridadClave ? Prioridad::where('clave', $prioridadClave)->first() : null;

        return Mantenimiento::create([
            'folio' => 'MTO-TEST-'.uniqid(),
            'equipo_id' => $this->equipo->id,
            'sucursal_id' => $sucursal,
            'tipo_id' => TipoMantenimiento::first()->id,
            'prioridad_id' => $prioridad?->id,
            'estado_id' => CicloMantenimiento::estado('autorizado')->id,
            'problema_reportado' => 'Falla intermitente',
            'creado_por' => $this->admin->id,
        ]);
    }

    public function test_asignar_tecnico_genera_notificacion(): void
    {
        $orden = $this->nuevaOrden();

        $this->actingAs($this->admin)
            ->post(route('mantenimientos.asignaciones.store', $orden), ['tecnico_id' => $this->tecnico->id])
            ->assertRedirect();

        $this->assertDatabaseHas('notificaciones', [
            'usuario_id' => $this->tecnico->id,
            'tipo' => 'mantenimiento_asignado',
        ]);
    }

    public function test_cambio_de_estado_notifica_al_tecnico_asignado(): void
    {
        $this->actingAs($this->admin);

        $orden = $this->nuevaOrden();
        $orden->asignaciones()->create([
            'tecnico_id' => $this->tecnico->id,
            'asignado_por' => $this->admin->id,
            'asignado_at' => now(),
        ]);
        $realizado = CicloMantenimiento::estado('realizado');

        $orden->historialEstados()->create([
            'estado_origen_id' => $orden->estado_id,
            'estado_destino_id' => $realizado->id,
            'cambiado_por' => $this->admin->id,
            'cambiado_at' => now(),
        ]);

        $this->assertDatabaseHas('notificaciones', [
            'usuario_id' => $this->tecnico->id,
            'tipo' => 'mantenimiento_modificado',
        ]);
        // Quien hizo el cambio no se auto-notifica.
        $this->assertDatabaseMissing('notificaciones', [
            'usuario_id' => $this->admin->id,
            'tipo' => 'mantenimiento_modificado',
        ]);
    }

    public function test_no_duplica_notificacion_sin_leer_del_mismo_evento(): void
    {
        Notificaciones::crear($this->tecnico->id, 'asignacion', 'Orden X', null, ['ref' => 'orden:99']);
        Notificaciones::crear($this->tecnico->id, 'asignacion', 'Orden X otra vez', null, ['ref' => 'orden:99']);

        $this->assertSame(1, Notificacion::where('usuario_id', $this->tecnico->id)->count());
    }

    public function test_comando_notifica_preventivos_proximos_y_vencidos(): void
    {
        $tipo = TipoMantenimiento::where('categoria', 'preventivo')->first();

        PlanMantenimiento::create([
            'equipo_id' => $this->equipo->id, 'tipo_mantenimiento_id' => $tipo->id,
            'tipo_frecuencia' => 'mensual', 'valor_frecuencia' => 1,
            'proxima_fecha' => today()->addDays(3), 'dias_aviso_anticipado' => 7,
            'tecnico_id' => $this->tecnico->id, 'estado' => 'activo',
        ]);
        PlanMantenimiento::create([
            'equipo_id' => $this->equipo->id, 'tipo_mantenimiento_id' => $tipo->id,
            'tipo_frecuencia' => 'mensual', 'valor_frecuencia' => 1,
            'proxima_fecha' => today()->subDays(5), 'dias_aviso_anticipado' => 7,
            'tecnico_id' => $this->tecnico->id, 'estado' => 'activo',
        ]);

        $this->artisan('sigam:notificar-preventivos')->assertSuccessful();

        $this->assertDatabaseHas('notificaciones', ['usuario_id' => $this->tecnico->id, 'tipo' => 'mantenimiento_proximo']);
        $this->assertDatabaseHas('notificaciones', ['usuario_id' => $this->tecnico->id, 'tipo' => 'mantenimiento_vencido']);
    }

    public function test_index_filtra_no_leidas_y_marca_todas(): void
    {
        Notificaciones::crear($this->admin->id, 'asignacion', 'A', null, ['ref' => 'a']);
        Notificaciones::crear($this->admin->id, 'urgencia', 'B', null, ['ref' => 'b']);
        Notificacion::where('titulo', 'A')->update(['leida_at' => now()]);

        $this->actingAs($this->admin)
            ->get(route('notificaciones.index', ['ver' => 'no_leidas']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('notificaciones.total', 1)
                ->where('notificaciones.per_page', 15)
                ->where('noLeidas', 1));

        $this->actingAs($this->admin)->put(route('notificaciones.leer_todas'))->assertRedirect();
        $this->assertSame(0, $this->admin->notificaciones()->noLeidas()->count());
    }

    public function test_no_puede_marcar_notificacion_ajena(): void
    {
        $ajena = Notificacion::create(['usuario_id' => $this->tecnico->id, 'tipo' => 'asignacion', 'titulo' => 'X']);

        $this->actingAs($this->admin)
            ->put(route('notificaciones.leida', $ajena))
            ->assertForbidden();
    }

    public function test_contador_no_leidas_json(): void
    {
        Notificaciones::crear($this->admin->id, 'urgencia', 'X', null, ['ref' => 'x']);

        $this->actingAs($this->admin)
            ->getJson(route('notificaciones.no_leidas'))
            ->assertOk()
            ->assertJson(['total' => 1]);
    }
}
