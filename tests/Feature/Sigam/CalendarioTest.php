<?php

namespace Tests\Feature\Sigam;

use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\Usuario;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarioTest extends TestCase
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

    public function test_la_tarea_lleva_clasificacion_responsables_y_estado_legible(): void
    {
        $proyecto = Proyecto::create(['nombre' => 'Remodelación', 'clave' => 'remodelacion']);
        $tarea = Tarea::create([
            'titulo' => 'Pintar fachada', 'descripcion' => 'Pintar fachada', 'fecha_limite' => now()->startOfMonth()->addDays(10),
            'estado' => 'asignada', 'clasificacion' => 'proyecto', 'proyecto_id' => $proyecto->id,
        ]);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);

        $this->actingAs($this->supervisor)->get(route('calendario.index'))
            ->assertInertia(fn ($p) => $p
                ->where('eventos.0.tipo_evento', 'tarea')
                ->where('eventos.0.clasificacion', 'proyecto')
                ->where('eventos.0.clasificacion_nombre', 'REMODELACIÓN')
                ->where('eventos.0.estado_nombre', 'Asignada')
                ->where('eventos.0.personas.0', $this->supervisor->nombre)
                ->etc());
    }

    public function test_los_catalogos_de_alta_solo_se_envian_al_pedirlos(): void
    {
        $this->actingAs($this->supervisor)->get(route('calendario.index'))
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('Mantenimiento/Calendario/Index')->missing('formularios'));

        $this->actingAs($this->supervisor)->get(route('calendario.index'))
            ->assertInertia(fn ($p) => $p
                ->reloadOnly('formularios', fn ($p) => $p
                    ->has('formularios.equipos')
                    ->has('formularios.prioridades')
                    ->has('formularios.usuarios')));
    }
}
