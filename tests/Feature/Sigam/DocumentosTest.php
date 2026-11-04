<?php

namespace Tests\Feature\Sigam;

use App\Models\Equipo;
use App\Models\Norma;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\Usuario;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentosTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Equipo $equipo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([RolesPermisosSeeder::class, CatalogosSeeder::class]);
        Storage::fake('local');

        $this->admin = Usuario::factory()->create();
        $this->admin->assignRole('superadministrador');
        $sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Matriz', 'estado' => 'activo']);
        $this->equipo = Equipo::create(['codigo_activo' => 'EQ-DOC', 'descripcion' => 'A', 'sucursal_id' => $sucursal->id]);
    }

    public function test_sube_descarga_previsualiza_y_elimina_un_documento(): void
    {
        $this->actingAs($this->admin)->post(route('documentos.store'), [
            'archivo' => UploadedFile::fake()->image('foto.jpg'),
            'relacionable_tipo' => 'equipo',
            'relacionable_id' => $this->equipo->id,
            'titulo' => 'Foto del equipo',
            'visibilidad' => 'privado',
        ])->assertRedirect();

        $documento = $this->equipo->fresh()->documentos()->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('documentos.ver', $documento))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('documentos.download', $documento))
            ->assertOk();

        $this->actingAs($this->admin)
            ->delete(route('documentos.destroy', $documento))
            ->assertRedirect();

        $this->assertSoftDeleted($documento);
    }

    /** Regresión: a Norma le faltaba el trait TieneDocumentos y no aceptaba adjuntos. */
    public function test_puede_adjuntar_documentos_a_una_norma_una_sucursal_y_un_proveedor(): void
    {
        $norma = Norma::create(['codigo' => 'NOM-DOC', 'nombre' => 'Norma con anexos', 'estado' => 'activo']);
        $sucursal = Sucursal::where('codigo', 'S1')->firstOrFail();
        $proveedor = Proveedor::create(['razon_social' => 'Proveedor Doc SA', 'estado' => 'activo']);

        foreach ([
            ['norma', $norma],
            ['sucursal', $sucursal],
            ['proveedor', $proveedor],
        ] as [$tipo, $entidad]) {
            $this->actingAs($this->admin)->post(route('documentos.store'), [
                'archivo' => UploadedFile::fake()->create("anexo-{$tipo}.pdf", 100, 'application/pdf'),
                'relacionable_tipo' => $tipo,
                'relacionable_id' => $entidad->id,
                'visibilidad' => 'privado',
            ])->assertRedirect();

            $this->assertSame(1, $entidad->fresh()->documentos()->count());
        }
    }

    public function test_con_la_nube_configurada_los_archivos_se_guardan_y_sirven_desde_alla(): void
    {
        Storage::fake('s3');
        config(['filesystems.documentos' => 's3']);

        $this->actingAs($this->admin)->post(route('documentos.store'), [
            'archivo' => UploadedFile::fake()->image('foto.jpg'),
            'relacionable_tipo' => 'equipo',
            'relacionable_id' => $this->equipo->id,
        ])->assertRedirect();

        $documento = $this->equipo->fresh()->documentos()->firstOrFail();
        $this->assertSame('s3', $documento->disco);
        Storage::disk('s3')->assertExists($documento->ruta);
        Storage::disk('local')->assertMissing($documento->ruta);

        $this->actingAs($this->admin)
            ->get(route('documentos.ver', $documento))
            ->assertOk();
    }

    public function test_el_comando_mueve_a_la_nube_los_documentos_que_siguen_en_el_servidor(): void
    {
        $this->actingAs($this->admin)->post(route('documentos.store'), [
            'archivo' => UploadedFile::fake()->image('vieja.jpg'),
            'relacionable_tipo' => 'equipo',
            'relacionable_id' => $this->equipo->id,
        ])->assertRedirect();
        $documento = $this->equipo->fresh()->documentos()->firstOrFail();
        $this->assertSame('local', $documento->disco);

        Storage::fake('s3');
        config(['filesystems.documentos' => 's3']);

        $this->artisan('sigam:documentos-a-la-nube')->assertSuccessful();

        $this->assertSame('s3', $documento->fresh()->disco);
        Storage::disk('s3')->assertExists($documento->ruta);
        $this->actingAs($this->admin)
            ->get(route('documentos.ver', $documento))
            ->assertOk();
    }

    public function test_sirve_el_archivo_completo_con_su_tipo_y_da_404_si_ya_no_existe(): void
    {
        $this->actingAs($this->admin)->post(route('documentos.store'), [
            'archivo' => UploadedFile::fake()->createWithContent('reporte.pdf', '%PDF-1.4 contenido'),
            'relacionable_tipo' => 'equipo',
            'relacionable_id' => $this->equipo->id,
        ])->assertRedirect();
        $documento = $this->equipo->fresh()->documentos()->firstOrFail();

        $vista = $this->actingAs($this->admin)->get(route('documentos.ver', $documento));
        $vista->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Content-Length', '18');
        $this->assertStringContainsString('inline', $vista->headers->get('Content-Disposition'));
        $this->assertSame('%PDF-1.4 contenido', $vista->streamedContent());

        $descarga = $this->actingAs($this->admin)->get(route('documentos.download', $documento));
        $this->assertStringContainsString('attachment', $descarga->headers->get('Content-Disposition'));
        $this->assertSame('%PDF-1.4 contenido', $descarga->streamedContent());

        Storage::disk('local')->delete($documento->ruta);
        $this->actingAs($this->admin)->get(route('documentos.ver', $documento))->assertNotFound();
    }
}
