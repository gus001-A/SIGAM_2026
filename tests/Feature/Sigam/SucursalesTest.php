<?php

namespace Tests\Feature\Sigam;

use App\Models\Sucursal;
use App\Models\Usuario;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SucursalesTest extends TestCase
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

    public function test_crea_una_sucursal(): void
    {
        $sucursal = null;

        $this->actingAs($this->admin)
            ->post(route('sucursales.store'), [
                'codigo' => 'SUC-01',
                'nombre' => 'Hospital Central',
                'correo' => 'central@hospital.test',
                'estado' => 'activo',
            ])
            ->assertRedirect();

        $sucursal = Sucursal::firstOrFail();
        $this->assertSame('Hospital Central', $sucursal->nombre);
        $this->assertSame('activo', $sucursal->estado);
    }

    public function test_correo_invalido_es_rechazado(): void
    {
        $this->actingAs($this->admin)
            ->from(route('sucursales.create'))
            ->post(route('sucursales.store'), [
                'codigo' => 'SUC-02',
                'nombre' => 'Sur',
                'correo' => 'no-es-correo',
                'estado' => 'activo',
            ])
            ->assertSessionHasErrors('correo');
    }

    public function test_listado_filtra_por_columna_y_pagina_15(): void
    {
        foreach (range(1, 20) as $n) {
            Sucursal::create(['codigo' => "SUC-{$n}", 'nombre' => "Sucursal {$n}", 'estado' => 'activo']);
        }

        $this->actingAs($this->admin)
            ->get(route('sucursales.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('sucursales.per_page', 15)
                ->where('orden.campo', 'nombre'));
    }

    public function test_baja_logica_y_reactivacion(): void
    {
        $sucursal = Sucursal::create(['codigo' => 'SUC-09', 'nombre' => 'Poniente', 'estado' => 'activo']);

        $this->actingAs($this->admin)
            ->delete(route('sucursales.destroy', $sucursal))
            ->assertRedirect(route('sucursales.index'));

        $this->assertSoftDeleted($sucursal);

        $this->actingAs($this->admin)
            ->put(route('sucursales.restore', $sucursal->id))
            ->assertRedirect();

        $sucursal->refresh();
        $this->assertNull($sucursal->deleted_at);
        $this->assertSame('activo', $sucursal->estado);
    }

    public function test_sucursales_inactivas_se_listan_al_filtrar_por_estado(): void
    {
        $sucursal = Sucursal::create(['codigo' => 'SUC-10', 'nombre' => 'Norte', 'estado' => 'activo']);
        $this->actingAs($this->admin)->delete(route('sucursales.destroy', $sucursal));

        $this->actingAs($this->admin)
            ->get(route('sucursales.index', ['estado' => 'inactivo']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('sucursales.total', 1)
                ->where('sucursales.data.0.nombre', 'Norte'));
    }

    public function test_tecnico_no_puede_crear_sucursales(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');

        $this->actingAs($tecnico)
            ->post(route('sucursales.store'), ['codigo' => 'X', 'nombre' => 'Y', 'estado' => 'activo'])
            ->assertForbidden();
    }
}
