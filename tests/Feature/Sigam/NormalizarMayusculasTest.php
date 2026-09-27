<?php

namespace Tests\Feature\Sigam;

use App\Models\Notificacion;
use App\Models\Prioridad;
use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalizarMayusculasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesPermisosSeeder::class, CatalogosSeeder::class]);
    }

    public function test_sube_a_mayusculas_registros_existentes_sin_tocarlos_de_nuevo(): void
    {
        $usuario = Usuario::factory()->create(['nombre' => 'Juan Pérez', 'apellidos' => 'García']);
        // Se guarda directo en la BD para simular un registro sembrado antes
        // de que existiera ConvierteMayusculas (saltando el evento `saving`).
        Usuario::withoutEvents(function () use ($usuario) {
            $usuario->newQuery()->where('id', $usuario->id)->update(['nombre' => 'Juan Pérez']);
        });

        $this->assertSame('Juan Pérez', $usuario->fresh()->nombre);

        $this->artisan('sigam:normalizar-mayusculas')->assertSuccessful();

        $this->assertSame('JUAN PÉREZ', $usuario->fresh()->nombre);
        $this->assertSame('GARCÍA', $usuario->fresh()->apellidos);
    }

    public function test_dry_run_no_guarda_cambios(): void
    {
        $usuario = Usuario::factory()->create();
        Usuario::withoutEvents(fn () => $usuario->newQuery()->where('id', $usuario->id)->update(['nombre' => 'sin mayusculas']));

        $this->artisan('sigam:normalizar-mayusculas --dry-run')->assertSuccessful();

        $this->assertSame('sin mayusculas', $usuario->fresh()->nombre);
    }

    public function test_no_genera_auditoria_ni_notificaciones_de_ruido(): void
    {
        $usuario = Usuario::factory()->create();
        Usuario::withoutEvents(fn () => $usuario->newQuery()->where('id', $usuario->id)->update(['nombre' => 'minusculas']));
        $antes = RegistroAuditoria::count();
        $antesNotis = Notificacion::count();

        $this->artisan('sigam:normalizar-mayusculas')->assertSuccessful();

        $this->assertSame($antes, RegistroAuditoria::count());
        $this->assertSame($antesNotis, Notificacion::count());
    }

    public function test_no_toca_campos_excluidos_como_clave_o_correo(): void
    {
        $prioridad = Prioridad::where('clave', 'normal')->firstOrFail();
        $claveOriginal = $prioridad->clave;

        $usuario = Usuario::factory()->create(['email' => 'correo.mixto@Ejemplo.com']);

        $this->artisan('sigam:normalizar-mayusculas')->assertSuccessful();

        $this->assertSame($claveOriginal, $prioridad->fresh()->clave);
        $this->assertSame('correo.mixto@Ejemplo.com', $usuario->fresh()->email);
    }
}
