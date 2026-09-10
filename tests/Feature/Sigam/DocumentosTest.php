<?php

namespace Tests\Feature\Sigam;

use App\Models\Equipo;
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
}
