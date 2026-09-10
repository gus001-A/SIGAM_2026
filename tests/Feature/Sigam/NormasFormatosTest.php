<?php

namespace Tests\Feature\Sigam;

use App\Models\Formato;
use App\Models\Norma;
use App\Models\Usuario;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormasFormatosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesPermisosSeeder::class);

        $this->admin = Usuario::factory()->create();
        $this->admin->assignRole('superadministrador');
    }

    // ---- Normas ----------------------------------------------------

    public function test_crea_norma_y_valida_fecha_revision(): void
    {
        $this->actingAs($this->admin)
            ->post(route('normas.store'), [
                'codigo' => 'NOM-137-SSA1-2008',
                'nombre' => 'Etiquetado de dispositivos médicos',
                'version' => '2008',
                'fecha_vigencia' => '2020-01-01',
                'fecha_revision' => '2019-01-01',
                'estado' => 'activo',
            ])
            ->assertSessionHasErrors('fecha_revision');

        $this->actingAs($this->admin)
            ->post(route('normas.store'), [
                'codigo' => 'NOM-137-SSA1-2008',
                'nombre' => 'Etiquetado de dispositivos médicos',
                'fecha_vigencia' => '2020-01-01',
                'fecha_revision' => '2026-01-01',
                'estado' => 'activo',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('normas', ['codigo' => 'NOM-137-SSA1-2008']);
    }

    public function test_norma_codigo_duplicado_es_rechazado(): void
    {
        Norma::create(['codigo' => 'NOM-001', 'nombre' => 'A', 'estado' => 'activo']);

        $this->actingAs($this->admin)
            ->from(route('normas.create'))
            ->post(route('normas.store'), ['codigo' => 'NOM-001', 'nombre' => 'B', 'estado' => 'activo'])
            ->assertSessionHasErrors('codigo');
    }

    public function test_norma_baja_logica_y_reactivacion(): void
    {
        $norma = Norma::create(['codigo' => 'NOM-009', 'nombre' => 'X', 'estado' => 'activo']);

        $this->actingAs($this->admin)->delete(route('normas.destroy', $norma))->assertRedirect();
        $this->assertSoftDeleted($norma);

        $this->actingAs($this->admin)->put(route('normas.restore', $norma->id))->assertRedirect();
        $norma->refresh();
        $this->assertNull($norma->deleted_at);
        $this->assertSame('activo', $norma->estado);
    }

    public function test_listado_normas_filtra_por_columna(): void
    {
        Norma::create(['codigo' => 'NOM-004', 'nombre' => 'Expediente clínico', 'estado' => 'activo']);
        Norma::create(['codigo' => 'ISO-9001', 'nombre' => 'Gestión de calidad', 'estado' => 'activo']);

        $this->actingAs($this->admin)
            ->get(route('normas.index', ['codigo' => 'NOM']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('normas.total', 1)
                ->where('normas.per_page', 15)
                ->where('normas.data.0.codigo', 'NOM-004'));
    }

    // ---- Formatos ------------------------------------------------

    public function test_crea_formato_con_campos_y_genera_claves(): void
    {
        $this->actingAs($this->admin)
            ->post(route('formatos.store'), [
                'nombre' => 'Checklist preventivo',
                'version' => '1.0',
                'estado' => 'activo',
                'campos' => [
                    ['etiqueta' => 'Nivel de aceite', 'tipo' => 'seleccion', 'obligatorio' => true, 'opciones' => ['Bajo', 'Normal', 'Alto']],
                    ['etiqueta' => 'Observaciones', 'tipo' => 'area_texto', 'obligatorio' => false],
                ],
            ])
            ->assertRedirect();

        $formato = Formato::with('campos')->firstOrFail();
        $this->assertCount(2, $formato->campos);
        $this->assertSame('nivel_de_aceite', $formato->campos[0]->clave);
        $this->assertSame(0, $formato->campos[0]->orden);
        $this->assertEqualsCanonicalizing(['Bajo', 'Normal', 'Alto'], $formato->campos[0]->opciones);
    }

    public function test_editar_formato_reescribe_los_campos(): void
    {
        $this->actingAs($this->admin)->post(route('formatos.store'), [
            'nombre' => 'F1', 'version' => '1.0', 'estado' => 'activo',
            'campos' => [['etiqueta' => 'Campo viejo', 'tipo' => 'texto']],
        ]);
        $formato = Formato::firstOrFail();

        $this->actingAs($this->admin)->put(route('formatos.update', $formato), [
            'nombre' => 'F1', 'version' => '1.1', 'estado' => 'activo',
            'campos' => [
                ['etiqueta' => 'Campo nuevo A', 'tipo' => 'numero'],
                ['etiqueta' => 'Campo nuevo B', 'tipo' => 'fecha'],
            ],
        ])->assertRedirect();

        $formato->refresh()->load('campos');
        $this->assertSame('1.1', $formato->version);
        $this->assertEqualsCanonicalizing(['Campo nuevo A', 'Campo nuevo B'], $formato->campos->pluck('etiqueta')->all());
    }

    public function test_formato_campo_con_tipo_invalido_es_rechazado(): void
    {
        $this->actingAs($this->admin)
            ->from(route('formatos.create'))
            ->post(route('formatos.store'), [
                'nombre' => 'F', 'estado' => 'activo',
                'campos' => [['etiqueta' => 'X', 'tipo' => 'inexistente']],
            ])
            ->assertSessionHasErrors('campos.0.tipo');
    }
}
