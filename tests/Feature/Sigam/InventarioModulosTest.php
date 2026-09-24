<?php

namespace Tests\Feature\Sigam;

use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\TipoUbicacion;
use App\Models\Ubicacion;
use App\Models\Usuario;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InventarioModulosTest extends TestCase
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

    // ---- Proveedores -------------------------------------------------

    public function test_crea_proveedor_y_filtra_por_columna(): void
    {
        $this->actingAs($this->admin)
            ->post(route('proveedores.store'), [
                'razon_social' => 'Refacciones Industriales SA',
                'rfc' => 'RIN990101AB2',
                'especialidad' => 'Compresores',
                'estado' => 'activo',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('proveedores', ['rfc' => 'RIN990101AB2']);

        Proveedor::create(['razon_social' => 'Otra Empresa', 'estado' => 'activo']);

        $this->actingAs($this->admin)
            ->get(route('proveedores.index', ['razon_social' => 'Refacciones']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('proveedores.total', 1)
                ->where('proveedores.per_page', 15)
                ->where('proveedores.data.0.razon_social', 'REFACCIONES INDUSTRIALES SA'));
    }

    public function test_crea_proveedor_con_documentos_adjuntos(): void
    {
        $archivo = UploadedFile::fake()->create('contrato.pdf', 200, 'application/pdf');

        $this->actingAs($this->admin)
            ->post(route('proveedores.store'), [
                'razon_social' => 'Servicios Técnicos SA',
                'estado' => 'activo',
                'documentos' => [$archivo],
            ])
            ->assertRedirect();

        $proveedor = Proveedor::where('razon_social', 'SERVICIOS TÉCNICOS SA')->firstOrFail();
        $documento = $proveedor->documentos()->wherePivot('rol', 'contrato')->firstOrFail();
        $this->assertSame('CONTRATO.PDF', $documento->nombre_original);
    }

    public function test_proveedor_baja_logica_y_reactivacion(): void
    {
        $proveedor = Proveedor::create(['razon_social' => 'Baja SA', 'estado' => 'activo']);

        $this->actingAs($this->admin)->delete(route('proveedores.destroy', $proveedor))->assertRedirect();
        $this->assertSoftDeleted($proveedor);

        $this->actingAs($this->admin)->put(route('proveedores.restore', $proveedor->id))->assertRedirect();
        $proveedor->refresh();
        $this->assertNull($proveedor->deleted_at);
        $this->assertSame('activo', $proveedor->estado);
    }

    // ---- Sucursales: código automático -----------------------------

    public function test_sucursal_genera_codigo_automatico_si_se_deja_en_blanco(): void
    {
        $this->actingAs($this->admin)
            ->post(route('sucursales.store'), ['nombre' => 'Hospital Central', 'estado' => 'activo'])
            ->assertRedirect();

        $sucursal = Sucursal::firstOrFail();
        $this->assertSame('HOSP-01', $sucursal->codigo);

        // Una segunda sucursal con el mismo prefijo de 4 letras avanza el consecutivo.
        $this->actingAs($this->admin)
            ->post(route('sucursales.store'), ['nombre' => 'Hospital Norte', 'estado' => 'activo'])
            ->assertRedirect();

        $this->assertSame('HOSP-02', Sucursal::where('nombre', 'HOSPITAL NORTE')->value('codigo'));
    }

    // ---- Ubicaciones ----------------------------------------------

    public function test_ubicacion_genera_codigo_automatico_y_ruta_de_trazabilidad(): void
    {
        $sucursal = Sucursal::create(['codigo' => 'CSAN-01', 'nombre' => 'Clínica San Ángel', 'estado' => 'activo']);

        $this->actingAs($this->admin)
            ->post(route('ubicaciones.store'), [
                'sucursal_id' => $sucursal->id,
                'nombre' => 'Urgencias',
                'estado' => 'activo',
            ])
            ->assertRedirect();

        $padre = Ubicacion::where('nombre', 'URGENCIAS')->firstOrFail();
        $this->assertSame('URGE-01', $padre->codigo);
        $this->assertSame('CSAN-01/URGE-01', $padre->ruta);

        $this->actingAs($this->admin)
            ->post(route('ubicaciones.store'), [
                'sucursal_id' => $sucursal->id,
                'padre_id' => $padre->id,
                'nombre' => 'Sala de choque',
                'estado' => 'activo',
            ])
            ->assertRedirect();

        $hija = Ubicacion::where('nombre', 'SALA DE CHOQUE')->firstOrFail();
        $this->assertSame('SALA-01', $hija->codigo);
        $this->assertSame('CSAN-01/URGE-01/SALA-01', $hija->ruta);
    }

    public function test_crea_arbol_de_ubicaciones_con_profundidad(): void
    {
        $sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Matriz', 'estado' => 'activo']);
        $tipo = TipoUbicacion::query()->firstOrCreate(['clave' => 'area'], ['nombre' => 'Área', 'estado' => 'activo']);

        $this->actingAs($this->admin)
            ->post(route('ubicaciones.store'), [
                'sucursal_id' => $sucursal->id,
                'tipo_id' => $tipo->id,
                'codigo' => 'A-01',
                'nombre' => 'Planta baja',
                'estado' => 'activo',
            ])
            ->assertRedirect();

        $raiz = Ubicacion::firstOrFail();
        $this->assertSame(0, $raiz->profundidad);

        $this->actingAs($this->admin)
            ->post(route('ubicaciones.store'), [
                'sucursal_id' => $sucursal->id,
                'padre_id' => $raiz->id,
                'codigo' => 'A-01-01',
                'nombre' => 'Consultorio 1',
                'estado' => 'activo',
            ])
            ->assertRedirect();

        $hija = Ubicacion::where('codigo', 'A-01-01')->firstOrFail();
        $this->assertSame(1, $hija->profundidad);
        $this->assertSame($raiz->id, $hija->padre_id);
    }

    public function test_no_se_desactiva_ubicacion_con_sububicaciones(): void
    {
        $sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Matriz', 'estado' => 'activo']);
        $raiz = Ubicacion::create(['sucursal_id' => $sucursal->id, 'codigo' => 'A-01', 'nombre' => 'A', 'estado' => 'activo', 'profundidad' => 0]);
        Ubicacion::create(['sucursal_id' => $sucursal->id, 'padre_id' => $raiz->id, 'codigo' => 'A-02', 'nombre' => 'B', 'estado' => 'activo', 'profundidad' => 1]);

        $this->actingAs($this->admin)
            ->from(route('ubicaciones.index', ['sucursal_id' => $sucursal->id]))
            ->delete(route('ubicaciones.destroy', $raiz))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted($raiz);
    }

    public function test_index_de_ubicaciones_arma_el_arbol(): void
    {
        $sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Matriz', 'estado' => 'activo']);
        $raiz = Ubicacion::create(['sucursal_id' => $sucursal->id, 'codigo' => 'A-01', 'nombre' => 'A', 'estado' => 'activo', 'profundidad' => 0]);
        Ubicacion::create(['sucursal_id' => $sucursal->id, 'padre_id' => $raiz->id, 'codigo' => 'A-02', 'nombre' => 'B', 'estado' => 'activo', 'profundidad' => 1]);

        $this->actingAs($this->admin)
            ->get(route('ubicaciones.index', ['sucursal_id' => $sucursal->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('arbol.0.nombre', 'A')
                ->where('arbol.0.hijas.0.nombre', 'B'));
    }
}
