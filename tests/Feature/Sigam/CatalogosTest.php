<?php

namespace Tests\Feature\Sigam;

use App\Models\Marca;
use App\Models\Usuario;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatalogosTest extends TestCase
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

    /** @return list<array{0:string}> */
    public static function catalogos(): array
    {
        return array_map(fn ($r) => [$r], [
            'catalogos.marcas.index',
            'catalogos.tipos_equipo.index',
            'catalogos.estados_equipo.index',
            'catalogos.tipos_ubicacion.index',
            'catalogos.tipos_mantenimiento.index',
            'catalogos.estados_mantenimiento.index',
            'catalogos.prioridades.index',
            'catalogos.materiales.index',
        ]);
    }

    #[DataProvider('catalogos')]
    public function test_cada_catalogo_carga(string $ruta): void
    {
        $this->actingAs($this->admin)
            ->get(route($ruta))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('registros.per_page', 15)
                ->has('orden.campo'));
    }

    public function test_crea_marca_y_genera_no_duplicada(): void
    {
        $this->actingAs($this->admin)
            ->post(route('catalogos.marcas.store'), ['nombre' => 'Carrier'])
            ->assertRedirect();

        $this->assertDatabaseHas('marcas', ['nombre' => 'Carrier', 'estado' => 'activo']);

        $this->actingAs($this->admin)
            ->from(route('catalogos.marcas.index'))
            ->post(route('catalogos.marcas.store'), ['nombre' => 'Carrier'])
            ->assertSessionHasErrors('nombre');
    }

    public function test_tipo_equipo_genera_clave_automatica(): void
    {
        $this->actingAs($this->admin)
            ->post(route('catalogos.tipos_equipo.store'), ['nombre' => 'Bomba de vacío'])
            ->assertRedirect();

        $this->assertDatabaseHas('tipos_equipo', ['nombre' => 'Bomba de vacío', 'clave' => 'bomba_de_vacio']);
    }

    public function test_desactivar_y_reactivar_catalogo(): void
    {
        $marca = Marca::create(['nombre' => 'Temporal', 'estado' => 'activo']);

        $this->actingAs($this->admin)->delete(route('catalogos.marcas.destroy', $marca->id))->assertRedirect();
        $this->assertSame('inactivo', $marca->fresh()->estado);

        $this->actingAs($this->admin)->put(route('catalogos.marcas.activar', $marca->id))->assertRedirect();
        $this->assertSame('activo', $marca->fresh()->estado);
    }

    public function test_filtro_por_columna_y_orden(): void
    {
        Marca::create(['nombre' => 'Alfa', 'estado' => 'activo']);
        Marca::create(['nombre' => 'Beta', 'estado' => 'activo']);

        $this->actingAs($this->admin)
            ->get(route('catalogos.marcas.index', ['nombre' => 'Alf', 'orden' => 'nombre', 'dir' => 'desc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('registros.total', 1)
                ->where('registros.data.0.nombre', 'Alfa')
                ->where('orden.dir', 'desc'));
    }

    public function test_prioridad_valida_nivel(): void
    {
        $this->actingAs($this->admin)
            ->from(route('catalogos.prioridades.index'))
            ->post(route('catalogos.prioridades.store'), ['nombre' => 'X', 'nivel' => 0])
            ->assertSessionHasErrors('nivel');

        $this->actingAs($this->admin)
            ->post(route('catalogos.prioridades.store'), ['nombre' => 'Crítica inmediata', 'nivel' => 1, 'minutos_respuesta' => 15])
            ->assertRedirect();

        $this->assertDatabaseHas('prioridades', ['nombre' => 'Crítica inmediata', 'nivel' => 1]);
    }

    public function test_tecnico_no_puede_editar_catalogos(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');
        $marca = Marca::create(['nombre' => 'Y', 'estado' => 'activo']);

        $this->actingAs($tecnico)
            ->put(route('catalogos.marcas.update', $marca->id), ['nombre' => 'Z'])
            ->assertForbidden();
    }
}
