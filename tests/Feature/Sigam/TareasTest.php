<?php

namespace Tests\Feature\Sigam;

use App\Models\Prioridad;
use App\Models\Tarea;
use App\Models\Usuario;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TareasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $supervisor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesPermisosSeeder::class);

        $this->supervisor = Usuario::factory()->create();
        $this->supervisor->assignRole('supervisor');
    }

    public function test_crear_tarea_requiere_al_menos_un_responsable(): void
    {
        $this->actingAs($this->supervisor)
            ->post(route('tareas.store'), ['descripcion' => 'Revisar extintores', 'fecha_limite' => now()->addDays(3)->toDateString()])
            ->assertSessionHasErrors('responsables');

        $this->assertSame(0, Tarea::count());
    }

    public function test_crear_tarea_queda_pendiente_con_responsable_e_historial(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');

        $this->actingAs($this->supervisor)
            ->post(route('tareas.store'), [
                'titulo' => 'Revisar extintores',
                'descripcion' => 'Revisar extintores',
                'fecha_limite' => now()->addDays(3)->toDateString(),
                'responsables' => [$tecnico->id],
            ])
            ->assertRedirect();

        $tarea = Tarea::firstOrFail();
        $this->assertSame('pendiente', $tarea->estado);
        $this->assertSame('REVISAR EXTINTORES', $tarea->descripcion);
        $this->assertTrue($tarea->responsables()->where('usuario_id', $tecnico->id)->wherePivotNull('desasignado_at')->exists());
        $this->assertSame(1, $tarea->historialEstados()->count());
    }

    public function test_tecnico_solo_ve_tareas_donde_es_responsable(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');
        $otro = Usuario::factory()->create();
        $otro->assignRole('tecnico');

        $suya = Tarea::create(['titulo' => 'Suya', 'descripcion' => 'Suya', 'fecha_limite' => now()->addDay(), 'estado' => 'pendiente']);
        $suya->responsables()->attach($tecnico->id, ['asignado_at' => now()]);

        $ajena = Tarea::create(['titulo' => 'Ajena', 'descripcion' => 'Ajena', 'fecha_limite' => now()->addDay(), 'estado' => 'pendiente']);
        $ajena->responsables()->attach($otro->id, ['asignado_at' => now()]);

        $this->actingAs($tecnico)
            ->get(route('tareas.index'))
            ->assertInertia(fn ($p) => $p->where('tareas.total', 1)->where('tareas.data.0.id', $suya->id));
    }

    public function test_transicion_a_realizada_requiere_nota_de_cierre(): void
    {
        $tarea = Tarea::create(['titulo' => 'Pintar bardas', 'descripcion' => 'Pintar bardas', 'fecha_limite' => now()->addDay(), 'estado' => 'en_proceso']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'realizada'])
            ->assertSessionHasErrors('nota');

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'realizada', 'nota' => 'Listo', 'costo' => 150])
            ->assertRedirect();

        $tarea->refresh();
        $this->assertSame('realizada', $tarea->estado);
        $this->assertSame('LISTO', $tarea->nota_cierre);
        $this->assertNotNull($tarea->realizada_at);
    }

    public function test_no_se_puede_saltar_de_pendiente_a_realizada(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'pendiente']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'realizada', 'nota' => 'Listo'])
            ->assertSessionHas('error');

        $this->assertSame('pendiente', $tarea->fresh()->estado);
    }

    public function test_cancelar_tarea_requiere_motivo(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'pendiente']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'cancelada'])
            ->assertSessionHasErrors('nota');

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'cancelada', 'nota' => 'Ya no aplica'])
            ->assertRedirect();

        $this->assertSame('cancelada', $tarea->fresh()->estado);
    }

    public function test_no_se_puede_retirar_al_ultimo_responsable(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'pendiente']);
        $tarea->asignaciones()->create(['usuario_id' => $this->supervisor->id, 'asignado_por' => $this->supervisor->id, 'asignado_at' => now()]);
        $asignacion = $tarea->asignaciones()->first();

        $this->actingAs($this->supervisor)
            ->delete(route('tareas.responsables.destroy', [$tarea, $asignacion]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tarea_responsables', ['id' => $asignacion->id, 'desasignado_at' => null]);
    }

    public function test_editar_actualiza_descripcion_fecha_y_prioridad(): void
    {
        $tarea = Tarea::create(['titulo' => 'Original', 'descripcion' => 'Original', 'fecha_limite' => now()->addDay(), 'estado' => 'pendiente']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);
        $prioridad = Prioridad::create(['nombre' => 'Alta', 'clave' => 'alta', 'nivel' => 1, 'estado' => 'activo']);

        $this->actingAs($this->supervisor)
            ->put(route('tareas.update', $tarea), [
                'titulo' => 'Original',
                'descripcion' => 'Actualizada',
                'fecha_limite' => now()->addDays(5)->toDateString(),
                'prioridad_id' => $prioridad->id,
            ])
            ->assertRedirect();

        $tarea->refresh();
        $this->assertSame('ACTUALIZADA', $tarea->descripcion);
        $this->assertSame($prioridad->id, $tarea->prioridad_id);
    }

    public function test_no_se_puede_editar_una_tarea_realizada(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'realizada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);

        $this->actingAs($this->supervisor)
            ->put(route('tareas.update', $tarea), ['titulo' => 'x', 'descripcion' => 'Cambiada', 'fecha_limite' => now()->addDay()->toDateString()])
            ->assertSessionHas('error');

        $this->assertSame('X', $tarea->fresh()->descripcion);
    }

    public function test_no_se_puede_eliminar_una_tarea_que_no_este_completada(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'pendiente']);

        $this->actingAs($this->supervisor)
            ->delete(route('tareas.destroy', $tarea))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tareas', ['id' => $tarea->id, 'deleted_at' => null]);
    }

    public function test_eliminar_tarea_realizada_hace_baja_logica(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'realizada']);

        $this->actingAs($this->supervisor)
            ->delete(route('tareas.destroy', $tarea))
            ->assertRedirect(route('tareas.index'));

        $this->assertSoftDeleted('tareas', ['id' => $tarea->id]);
    }

    public function test_tecnico_no_puede_eliminar_tareas(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'pendiente']);
        $tarea->responsables()->attach($tecnico->id, ['asignado_at' => now()]);

        $this->actingAs($tecnico)
            ->delete(route('tareas.destroy', $tarea))
            ->assertForbidden();
    }
}
