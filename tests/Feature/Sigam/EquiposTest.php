<?php

namespace Tests\Feature\Sigam;

use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Norma;
use App\Models\Sucursal;
use App\Models\Ubicacion;
use App\Models\Usuario;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class EquiposTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Sucursal $sucursal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([RolesPermisosSeeder::class, CatalogosSeeder::class]);

        $this->admin = Usuario::factory()->create();
        $this->admin->assignRole('superadministrador');
        $this->sucursal = Sucursal::create(['codigo' => 'S1', 'nombre' => 'Matriz', 'estado' => 'activo']);
    }

    public function test_registra_un_equipo_con_normas_e_historial_de_ubicacion(): void
    {
        $ubicacion = Ubicacion::create([
            'sucursal_id' => $this->sucursal->id, 'codigo' => 'A1', 'nombre' => 'Área 1', 'estado' => 'activo',
        ]);
        $norma = Norma::create(['codigo' => 'NOM-001', 'nombre' => 'Norma 1', 'estado' => 'activo']);

        $respuesta = $this->actingAs($this->admin)->post(route('equipos.store'), [
            'codigo_activo' => 'EQ-001',
            'descripcion' => 'Compresor',
            'sucursal_id' => $this->sucursal->id,
            'ubicacion_id' => $ubicacion->id,
            'estado_id' => EstadoEquipo::where('clave', 'operativo')->value('id'),
            'valor_adquisicion' => 15000,
            'especificaciones' => ['Voltaje' => '220 V'],
            'normas' => [$norma->id],
        ]);

        $equipo = Equipo::firstOrFail();
        $respuesta->assertRedirect(route('equipos.show', $equipo));

        $this->assertSame('EQ-001', $equipo->codigo_activo);
        $this->assertNotNull($equipo->token_qr);
        $this->assertEqualsCanonicalizing([$norma->id], $equipo->normas->pluck('id')->all());
        $this->assertSame(1, $equipo->historialUbicacion()->count());
        $this->assertSame('220 V', $equipo->especificaciones['Voltaje']);
    }

    public function test_cambio_de_ubicacion_al_editar_genera_registro_de_historial(): void
    {
        $u1 = Ubicacion::create(['sucursal_id' => $this->sucursal->id, 'codigo' => 'A1', 'nombre' => 'Área 1', 'estado' => 'activo']);
        $u2 = Ubicacion::create(['sucursal_id' => $this->sucursal->id, 'codigo' => 'A2', 'nombre' => 'Área 2', 'estado' => 'activo']);

        $equipo = Equipo::create([
            'codigo_activo' => 'EQ-002', 'descripcion' => 'Motor',
            'sucursal_id' => $this->sucursal->id, 'ubicacion_id' => $u1->id,
        ]);

        $this->actingAs($this->admin)->put(route('equipos.update', $equipo), [
            'codigo_activo' => 'EQ-002',
            'descripcion' => 'Motor',
            'sucursal_id' => $this->sucursal->id,
            'ubicacion_id' => $u2->id,
            'motivo_cambio_ubicacion' => 'Reubicación por obra',
        ])->assertRedirect(route('equipos.show', $equipo));

        $historial = $equipo->historialUbicacion()->firstOrFail();
        $this->assertSame($u1->id, $historial->ubicacion_origen_id);
        $this->assertSame($u2->id, $historial->ubicacion_destino_id);
        $this->assertSame('Reubicación por obra', $historial->motivo);
    }

    public function test_codigo_activo_duplicado_es_rechazado(): void
    {
        Equipo::create(['codigo_activo' => 'EQ-003', 'descripcion' => 'A', 'sucursal_id' => $this->sucursal->id]);

        $this->actingAs($this->admin)
            ->from(route('equipos.create'))
            ->post(route('equipos.store'), [
                'codigo_activo' => 'EQ-003',
                'descripcion' => 'B',
                'sucursal_id' => $this->sucursal->id,
            ])
            ->assertSessionHasErrors('codigo_activo');
    }

    public function test_baja_logica_del_equipo(): void
    {
        $equipo = Equipo::create(['codigo_activo' => 'EQ-004', 'descripcion' => 'A', 'sucursal_id' => $this->sucursal->id]);

        $this->actingAs($this->admin)
            ->delete(route('equipos.destroy', $equipo))
            ->assertRedirect(route('equipos.index'));

        $this->assertSoftDeleted($equipo);
    }

    public function test_la_pagina_qr_incluye_svg(): void
    {
        $equipo = Equipo::create(['codigo_activo' => 'EQ-005', 'descripcion' => 'A', 'sucursal_id' => $this->sucursal->id]);

        $this->actingAs($this->admin)
            ->get(route('equipos.qr', $equipo))
            ->assertSuccessful();
    }

    public function test_qr_en_json_y_descargas_pdf_e_imagen(): void
    {
        $equipo = Equipo::create(['codigo_activo' => 'EQ-QR', 'descripcion' => 'A', 'sucursal_id' => $this->sucursal->id]);

        $this->actingAs($this->admin)
            ->getJson(route('equipos.qr_data', $equipo))
            ->assertOk()
            ->assertJsonStructure(['codigo_activo', 'descripcion', 'url', 'png']);

        $pdf = $this->actingAs($this->admin)->get(route('equipos.qr_pdf', $equipo))->assertOk();
        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));

        $img = $this->actingAs($this->admin)->get(route('equipos.qr_img', $equipo))->assertOk();
        $this->assertStringContainsString('image/jpeg', $img->headers->get('content-type'));
    }

    public function test_valida_caracteres_y_fechas_del_formulario(): void
    {
        $this->actingAs($this->admin)
            ->from(route('equipos.create'))
            ->post(route('equipos.store'), [
                'codigo_activo' => 'EQ-001@inválido',
                'descripcion' => 'Texto con <script>',
                'sucursal_id' => $this->sucursal->id,
                'fecha_adquisicion' => now()->addYear()->toDateString(),
            ])
            ->assertSessionHasErrors(['codigo_activo', 'descripcion', 'fecha_adquisicion']);

        // La ñ y los signos de puntuación sí se aceptan.
        $this->actingAs($this->admin)
            ->post(route('equipos.store'), [
                'codigo_activo' => 'EQ-2024/ÑOÑO-01',
                'descripcion' => 'Compresor «grande», 5 HP (nuevo).',
                'sucursal_id' => $this->sucursal->id,
            ])
            ->assertRedirect();
    }

    /** Genera un .xlsx real (openspout) con esas filas y lo devuelve como archivo subido. */
    private function xlsx(array $filas): UploadedFile
    {
        $ruta = tempnam(sys_get_temp_dir(), 'test').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($ruta);
        foreach ($filas as $fila) {
            $writer->addRow(Row::fromValues($fila));
        }
        $writer->close();

        return new UploadedFile($ruta, 'equipos.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_importa_equipos_desde_excel(): void
    {
        Ubicacion::create(['sucursal_id' => $this->sucursal->id, 'codigo' => 'A1', 'nombre' => 'Área 1', 'estado' => 'activo']);

        $archivo = $this->xlsx([
            ['codigo_activo', 'descripcion', 'tipo', 'marca', 'sucursal', 'ubicacion', 'fecha_adquisicion', 'valor_adquisicion'],
            ['EQ-IMP-01', 'Compresor de aire «5HP»', 'Climatización', 'Carrier', 'Matriz', 'Área 1', '2024-03-15', '45000'],
            ['EQ-IMP-02', 'Bomba de agua', 'Hidráulica', 'Pedrollo', 'Sucursal Fantasma', '', '2024-04-01', '12000'],
        ]);

        $this->actingAs($this->admin)
            ->post(route('equipos.importar'), ['archivo' => $archivo])
            ->assertRedirect();

        $this->assertDatabaseHas('equipos', ['codigo_activo' => 'EQ-IMP-01', 'descripcion' => 'Compresor de aire «5HP»']);
        $this->assertDatabaseMissing('equipos', ['codigo_activo' => 'EQ-IMP-02']);
        $this->assertSame(1, Equipo::where('codigo_activo', 'EQ-IMP-01')->first()->historialUbicacion()->count());
    }

    public function test_no_acepta_un_csv_en_la_carga(): void
    {
        $csv = UploadedFile::fake()->createWithContent('equipos.csv', "codigo_activo\nX\n");

        $this->actingAs($this->admin)
            ->from(route('equipos.index'))
            ->post(route('equipos.importar'), ['archivo' => $csv])
            ->assertSessionHasErrors('archivo');
    }

    public function test_plantilla_de_importacion_es_un_xlsx(): void
    {
        $resp = $this->actingAs($this->admin)
            ->get(route('equipos.importar.plantilla'))
            ->assertOk();

        $this->assertStringContainsString('spreadsheetml', $resp->headers->get('content-type'));
    }

    public function test_tecnico_no_puede_importar_equipos(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');

        $this->actingAs($tecnico)
            ->post(route('equipos.importar'), ['archivo' => $this->xlsx([['codigo_activo'], ['X']])])
            ->assertForbidden();
    }

    public function test_tecnico_no_puede_crear_equipos(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');

        $this->actingAs($tecnico)
            ->post(route('equipos.store'), ['codigo_activo' => 'X', 'descripcion' => 'Y', 'sucursal_id' => $this->sucursal->id])
            ->assertForbidden();
    }

    public function test_listado_ordena_por_columna_permitida(): void
    {
        Equipo::create(['codigo_activo' => 'EQ-B', 'descripcion' => 'B', 'sucursal_id' => $this->sucursal->id, 'valor_adquisicion' => 100]);
        Equipo::create(['codigo_activo' => 'EQ-A', 'descripcion' => 'A', 'sucursal_id' => $this->sucursal->id, 'valor_adquisicion' => 900]);

        $this->actingAs($this->admin)
            ->get(route('equipos.index', ['orden' => 'valor_adquisicion', 'dir' => 'desc']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('orden.campo', 'valor_adquisicion')
                ->where('orden.dir', 'desc')
                ->where('equipos.data.0.codigo_activo', 'EQ-A'));
    }
}
