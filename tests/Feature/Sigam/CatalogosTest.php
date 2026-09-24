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

        $this->assertDatabaseHas('marcas', ['nombre' => 'CARRIER', 'estado' => 'activo']);

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

        $this->assertDatabaseHas('tipos_equipo', ['nombre' => 'BOMBA DE VACÍO', 'clave' => 'bomba_de_vacio']);
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
                ->where('registros.data.0.nombre', 'ALFA')
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

        $this->assertDatabaseHas('prioridades', ['nombre' => 'CRÍTICA INMEDIATA', 'nivel' => 1]);
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

    /**
     * El botón «+» junto a los selects de catálogo (<SelectCatalogo>) da de
     * alta / edita / desactiva sin salir del formulario: manda el header
     * `X-Alta-Rapida` y espera JSON en vez de una redirección.
     */
    public function test_alta_rapida_de_catalogo_responde_json(): void
    {
        $respuesta = $this->actingAs($this->admin)
            ->withHeaders(['X-Alta-Rapida' => '1'])
            ->postJson(route('catalogos.marcas.store'), ['nombre' => 'Trane']);

        $respuesta->assertOk()->assertJsonFragment(['nombre' => 'TRANE']);
        $id = $respuesta->json('id');

        $this->actingAs($this->admin)
            ->withHeaders(['X-Alta-Rapida' => '1'])
            ->putJson(route('catalogos.marcas.update', $id), ['nombre' => 'Trane Technologies'])
            ->assertOk()
            ->assertJsonFragment(['nombre' => 'TRANE TECHNOLOGIES']);

        $this->actingAs($this->admin)
            ->withHeaders(['X-Alta-Rapida' => '1'])
            ->deleteJson(route('catalogos.marcas.destroy', $id))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame('inactivo', Marca::find($id)->estado);
    }

    /** Sin el header de alta rápida, el comportamiento normal (redirect) no cambia. */
    public function test_alta_de_catalogo_sin_header_sigue_redirigiendo(): void
    {
        $this->actingAs($this->admin)
            ->post(route('catalogos.marcas.store'), ['nombre' => 'Daikin'])
            ->assertRedirect();
    }

    /** Catálogos de limpieza hospitalaria (observaciones generales del cliente §18-19). */
    public function test_catalogos_de_limpieza_traen_los_valores_sembrados(): void
    {
        $this->assertDatabaseHas('tipos_area', ['nombre' => 'ÁREA CRÍTICA', 'dias_limpieza' => 7]);
        $this->assertDatabaseHas('tipos_area', ['nombre' => 'ÁREA SEMI-CRÍTICA', 'dias_limpieza' => 15]);
        $this->assertDatabaseHas('tipos_area', ['nombre' => 'ÁREA NO CRÍTICA', 'dias_limpieza' => 30]);
        $this->assertDatabaseHas('tipos_limpieza', ['nombre' => 'RUTINARIA', 'frecuencia' => 'DIARIA']);
        $this->assertDatabaseHas('tipos_limpieza', ['nombre' => 'TERMINAL', 'frecuencia' => 'DESPUÉS DE CADA EVENTO']);
        $this->assertDatabaseHas('tipos_limpieza', ['nombre' => 'EXHAUSTIVA', 'frecuencia' => 'SEGÚN TIPO DE ÁREA']);
    }

    public function test_crea_tipo_de_area_con_dias_de_limpieza(): void
    {
        $this->actingAs($this->admin)
            ->post(route('catalogos.tipos_area.store'), ['nombre' => 'Área intermedia', 'dias_limpieza' => 20])
            ->assertRedirect();

        $this->assertDatabaseHas('tipos_area', ['nombre' => 'ÁREA INTERMEDIA', 'dias_limpieza' => 20]);
    }

    public function test_tipo_de_area_requiere_dias_de_limpieza(): void
    {
        $this->actingAs($this->admin)
            ->from(route('catalogos.tipos_area.index'))
            ->post(route('catalogos.tipos_area.store'), ['nombre' => 'Sin días'])
            ->assertSessionHasErrors('dias_limpieza');
    }
}
