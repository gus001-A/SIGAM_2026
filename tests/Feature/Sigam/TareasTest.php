<?php

namespace Tests\Feature\Sigam;

use App\Models\Prioridad;
use App\Models\Tarea;
use App\Models\Usuario;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TareasTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $supervisor;

    private Usuario $auditor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesPermisosSeeder::class);

        $this->supervisor = Usuario::factory()->create();
        $this->supervisor->assignRole('supervisor');

        $this->auditor = Usuario::factory()->create();
        $this->auditor->assignRole('auditor');
    }

    /** Inicia la tarea por la transición real, para que el responsable quede registrado como participante activo. */
    private function iniciar(Tarea $tarea): void
    {
        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'en_proceso', 'nota' => 'Inicio'])
            ->assertSessionHasNoErrors();
    }

    public function test_crear_tarea_requiere_al_menos_un_responsable(): void
    {
        $this->actingAs($this->supervisor)
            ->post(route('tareas.store'), ['descripcion' => 'Revisar extintores', 'fecha_limite' => now()->addDays(3)->toDateString()])
            ->assertSessionHasErrors('responsables');

        $this->assertSame(0, Tarea::count());
    }

    public function test_crear_tarea_queda_asignada_con_responsable_e_historial(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');

        $this->actingAs($this->supervisor)
            ->post(route('tareas.store'), [
                'titulo' => 'Revisar extintores',
                'descripcion' => 'Revisar extintores',
                'fecha_limite' => now()->addDays(3)->toDateString(),
                'responsables' => [$tecnico->id],
            ])
            ->assertRedirect();

        $tarea = Tarea::firstOrFail();
        $this->assertSame('asignada', $tarea->estado);
        $this->assertSame('REVISAR EXTINTORES', $tarea->descripcion);
        $this->assertTrue($tarea->responsables()->where('usuario_id', $tecnico->id)->wherePivotNull('desasignado_at')->exists());
        $this->assertSame(1, $tarea->historialEstados()->count());
    }

    public function test_tecnico_solo_ve_tareas_donde_es_responsable(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');
        $otro = Usuario::factory()->create();
        $otro->assignRole('tecnico');

        $suya = Tarea::create(['titulo' => 'Suya', 'descripcion' => 'Suya', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $suya->responsables()->attach($tecnico->id, ['asignado_at' => now()]);

        $ajena = Tarea::create(['titulo' => 'Ajena', 'descripcion' => 'Ajena', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $ajena->responsables()->attach($otro->id, ['asignado_at' => now()]);

        $this->actingAs($tecnico)
            ->get(route('tareas.index'))
            ->assertInertia(fn ($p) => $p->where('tareas.total', 1)->where('tareas.data.0.id', $suya->id));
    }

    public function test_transicion_a_completada_requiere_nota_de_cierre(): void
    {
        $tarea = Tarea::create(['titulo' => 'Pintar bardas', 'descripcion' => 'Pintar bardas', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);
        $this->iniciar($tarea);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'completada'])
            ->assertSessionHasErrors('nota');

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'completada', 'nota' => 'Listo', 'costo' => 150, 'evidencias' => [UploadedFile::fake()->image('completada.jpg')]])
            ->assertRedirect();

        $tarea->refresh();
        $this->assertSame('completada', $tarea->estado);
        $this->assertSame('LISTO', $tarea->nota_cierre);
        $this->assertNotNull($tarea->realizada_at);
    }

    public function test_dias_de_retraso_se_calculan_al_completar_una_tarea_vencida(): void
    {
        $tarea = Tarea::create(['titulo' => 'Limpiar cisterna', 'descripcion' => 'Limpiar cisterna', 'fecha_limite' => now()->subDays(3)->toDateString(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);
        $this->iniciar($tarea);

        $this->assertNull($tarea->diasRetraso());

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'completada', 'nota' => 'Listo', 'evidencias' => [UploadedFile::fake()->image('cisterna.jpg')]])
            ->assertRedirect();

        $this->assertSame(3, $tarea->refresh()->diasRetraso());
        $this->actingAs($this->supervisor)
            ->get(route('tareas.index', ['vista' => 'completadas']))
            ->assertInertia(fn ($p) => $p->where('tareas.total', 0));
        $this->actingAs($this->supervisor)
            ->get(route('tareas.show', $tarea))
            ->assertInertia(fn ($p) => $p->where('retrasoDias', 3));
    }

    public function test_tarea_entregada_a_tiempo_no_tiene_retraso(): void
    {
        $tarea = Tarea::create(['titulo' => 'Revisar techo', 'descripcion' => 'Revisar techo', 'fecha_limite' => now()->addDays(2)->toDateString(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);
        $this->iniciar($tarea);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'completada', 'nota' => 'Listo', 'evidencias' => [UploadedFile::fake()->image('techo.jpg')]])
            ->assertRedirect();

        $this->assertSame(0, $tarea->refresh()->diasRetraso());
    }

    public function test_no_se_puede_saltar_de_asignada_a_completada(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'completada', 'nota' => 'Listo'])
            ->assertSessionHas('error');

        $this->assertSame('asignada', $tarea->fresh()->estado);
    }

    public function test_cancelar_tarea_requiere_motivo(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'cancelada'])
            ->assertSessionHasErrors('nota');

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'cancelada', 'nota' => 'Ya no aplica', 'evidencias' => [UploadedFile::fake()->image('cancelada.jpg')]])
            ->assertRedirect();

        $this->assertSame('cancelada', $tarea->fresh()->estado);
    }

    public function test_no_se_puede_retirar_al_ultimo_responsable(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->asignaciones()->create(['usuario_id' => $this->supervisor->id, 'asignado_por' => $this->supervisor->id, 'asignado_at' => now()]);
        $asignacion = $tarea->asignaciones()->first();

        $this->actingAs($this->supervisor)
            ->delete(route('tareas.responsables.destroy', [$tarea, $asignacion]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tarea_responsables', ['id' => $asignacion->id, 'desasignado_at' => null]);
    }

    public function test_editar_actualiza_descripcion_fecha_y_prioridad(): void
    {
        $tarea = Tarea::create(['titulo' => 'Original', 'descripcion' => 'Original', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);
        $prioridad = Prioridad::create(['nombre' => 'Alta', 'clave' => 'alta', 'nivel' => 1, 'estado' => 'activo']);

        $this->actingAs($this->supervisor)
            ->put(route('tareas.update', $tarea), [
                'titulo' => 'Original',
                'descripcion' => 'Actualizada',
                'fecha_limite' => now()->addDays(5)->toDateString(),
                'prioridad_id' => $prioridad->id,
            ])
            ->assertRedirect();

        $tarea->refresh();
        $this->assertSame('ACTUALIZADA', $tarea->descripcion);
        $this->assertSame($prioridad->id, $tarea->prioridad_id);
    }

    public function test_no_se_puede_editar_una_tarea_cerrada(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'cerrada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);

        $this->actingAs($this->supervisor)
            ->put(route('tareas.update', $tarea), ['titulo' => 'x', 'descripcion' => 'Cambiada', 'fecha_limite' => now()->addDay()->toDateString()])
            ->assertSessionHas('error');

        $this->assertSame('X', $tarea->fresh()->descripcion);
    }

    public function test_no_se_puede_eliminar_una_tarea_que_no_este_cerrada(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'completada']);

        $this->actingAs($this->supervisor)
            ->delete(route('tareas.destroy', $tarea))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tareas', ['id' => $tarea->id, 'deleted_at' => null]);
    }

    public function test_eliminar_tarea_cerrada_hace_baja_logica(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'cerrada']);

        $this->actingAs($this->supervisor)
            ->delete(route('tareas.destroy', $tarea))
            ->assertRedirect(route('tareas.index'));

        $this->assertSoftDeleted('tareas', ['id' => $tarea->id]);
    }

    public function test_tecnico_no_puede_eliminar_tareas(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($tecnico->id, ['asignado_at' => now()]);

        $this->actingAs($tecnico)
            ->delete(route('tareas.destroy', $tarea))
            ->assertForbidden();
    }

    public function test_un_avance_admite_varias_fotos(): void
    {
        $tarea = Tarea::create(['titulo' => 'Pintar', 'descripcion' => 'Pintar', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), [
                'estado' => 'en_proceso',
                'nota' => 'Primer avance',
                'evidencias' => [
                    UploadedFile::fake()->image('uno.jpg'),
                    UploadedFile::fake()->image('dos.jpg'),
                    UploadedFile::fake()->image('tres.jpg'),
                ],
            ])
            ->assertRedirect();

        $movimiento = $tarea->historialEstados()->where('estado_destino', 'en_proceso')->firstOrFail();
        $this->assertCount(3, $movimiento->evidencias);
    }

    public function test_un_avance_no_exige_foto(): void
    {
        $tarea = Tarea::create(['titulo' => 'Pintar', 'descripcion' => 'Pintar', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now()]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'en_proceso', 'nota' => 'Sin foto'])
            ->assertSessionHasNoErrors();

        $this->assertSame('en_proceso', $tarea->refresh()->estado);
    }

    public function test_tarea_compartida_no_se_completa_hasta_que_avance_cada_participante(): void
    {
        $otro = Usuario::factory()->create();
        $otro->assignRole('tecnico');

        $tarea = Tarea::create(['titulo' => 'Pintar bardas', 'descripcion' => 'Pintar bardas', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now(), 'es_principal' => true]);
        $tarea->responsables()->attach($otro->id, ['asignado_at' => now()]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'en_proceso', 'nota' => 'Inicio'])
            ->assertSessionHasNoErrors();

        // El otro participante todavía no ha hecho nada: no se puede completar.
        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'completada', 'nota' => 'Listo'])
            ->assertSessionHasErrors('estado');
        $this->assertSame('en_proceso', $tarea->refresh()->estado);

        // Cada participante registra su avance, con su nombre en la bitácora.
        $this->actingAs($otro)
            ->post(route('tareas.avance', $tarea), ['nota' => 'Ya pinté la barda norte'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($tarea->historialEstados()->where('cambiado_por', $otro->id)->where('nota', 'like', '%NORTE%')->exists());
        $this->assertSame(0, $tarea->participantesPendientes()->count());

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'completada', 'nota' => 'Listo'])
            ->assertSessionHasNoErrors();
        $this->assertSame('completada', $tarea->refresh()->estado);
    }

    public function test_solo_el_principal_puede_completar_sin_esperar(): void
    {
        $otro = Usuario::factory()->create();
        $otro->assignRole('tecnico');

        $tarea = Tarea::create(['titulo' => 'Revisar', 'descripcion' => 'Revisar', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now(), 'es_principal' => true]);
        $tarea->responsables()->attach($otro->id, ['asignado_at' => now()]);

        $this->actingAs($this->supervisor)->post(route('tareas.transicion', $tarea), ['estado' => 'en_proceso', 'nota' => 'Inicio']);

        // Un participante que no es el principal no puede forzar la completada.
        $this->actingAs($otro)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'completada', 'nota' => 'Listo', 'cerrar_sin_esperar' => true])
            ->assertSessionHasErrors('estado');
        $this->assertSame('en_proceso', $tarea->refresh()->estado);

        // El principal sí puede, y la nota deja constancia de a quién no esperó.
        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'completada', 'nota' => 'Listo', 'cerrar_sin_esperar' => true])
            ->assertSessionHasNoErrors();

        $tarea->refresh();
        $this->assertSame('completada', $tarea->estado);
        $this->assertStringContainsString('COMPLETADA SIN ESPERAR A', $tarea->nota_cierre);
    }

    public function test_la_ficha_muestra_quien_edito_la_tarea_y_cuando(): void
    {
        $tarea = Tarea::create(['titulo' => 'Original', 'descripcion' => 'Original', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now(), 'es_principal' => true]);

        $this->actingAs($this->supervisor);
        $tarea->update(['titulo' => 'Cambiado']);

        $this->actingAs($this->supervisor)
            ->get(route('tareas.show', $tarea))
            ->assertInertia(fn ($p) => $p
                ->has('bitacora', 1)
                ->where('bitacora.0.accion', 'actualizar')
                ->where('bitacora.0.usuario', $this->supervisor->nombre_completo)
                ->where('bitacora.0.cambios.0.campo', 'titulo')
                ->where('bitacora.0.cambios.0.despues', 'CAMBIADO'));
    }

    public function test_la_bitacora_ignora_los_cambios_de_estado(): void
    {
        $tarea = Tarea::create(['titulo' => 'Estado', 'descripcion' => 'Estado', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now(), 'es_principal' => true]);

        $this->actingAs($this->supervisor);
        $tarea->update(['estado' => 'en_proceso', 'iniciada_at' => now()]);

        $this->actingAs($this->supervisor)
            ->get(route('tareas.show', $tarea))
            ->assertInertia(fn ($p) => $p->has('bitacora', 0));
    }

    public function test_un_avance_admite_pdf_y_word_y_rechaza_otros_archivos(): void
    {
        $tarea = Tarea::create(['titulo' => 'Informe', 'descripcion' => 'Informe', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now(), 'es_principal' => true]);
        $this->iniciar($tarea);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.avance', $tarea), [
                'nota' => 'Adjunto el informe',
                'evidencias' => [
                    UploadedFile::fake()->create('acta.pdf', 120, 'application/pdf'),
                    UploadedFile::fake()->create('informe.docx', 120, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
                ],
            ])
            ->assertSessionHasNoErrors();

        $movimiento = $tarea->historialEstados()->where('nota', 'like', '%INFORME%')->firstOrFail();
        $this->assertCount(2, $movimiento->evidencias);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.avance', $tarea), [
                'nota' => 'Con un ejecutable',
                'evidencias' => [UploadedFile::fake()->create('virus.exe', 10)],
            ])
            ->assertSessionHasErrors('evidencias.0');
    }

    public function test_un_avance_admite_hojas_de_calculo_presentaciones_y_csv(): void
    {
        $tarea = Tarea::create(['titulo' => 'Reporte', 'descripcion' => 'Reporte', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now(), 'es_principal' => true]);
        $this->iniciar($tarea);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.avance', $tarea), [
                'nota' => 'Adjunto hojas',
                'evidencias' => [
                    UploadedFile::fake()->create('datos.xlsx', 120, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                    UploadedFile::fake()->create('presentacion.pptx', 120, 'application/vnd.openxmlformats-officedocument.presentationml.presentation'),
                    UploadedFile::fake()->create('tabla.csv', 10, 'text/csv'),
                ],
            ])
            ->assertSessionHasNoErrors();

        $movimiento = $tarea->historialEstados()->where('nota', 'like', '%HOJAS%')->firstOrFail();
        $this->assertCount(3, $movimiento->evidencias);
    }

    /** El enlace es opcional, se guarda tal cual (nunca en mayúsculas) y se valida como URL. */
    public function test_un_avance_admite_un_enlace_opcional_sin_convertirlo_a_mayusculas(): void
    {
        $tarea = Tarea::create(['titulo' => 'Con enlace', 'descripcion' => 'Con enlace', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now(), 'es_principal' => true]);
        $this->iniciar($tarea);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.avance', $tarea), ['nota' => 'Aquí el enlace', 'enlace' => 'https://ejemplo.com/Carpeta-Compartida'])
            ->assertSessionHasNoErrors();

        $movimiento = $tarea->historialEstados()->where('nota', 'like', '%ENLACE%')->firstOrFail();
        $this->assertSame('https://ejemplo.com/Carpeta-Compartida', $movimiento->enlace);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.avance', $tarea), ['nota' => 'Sin enlace, todo bien'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->supervisor)
            ->post(route('tareas.avance', $tarea), ['nota' => 'Enlace inválido', 'enlace' => 'no-es-una-url'])
            ->assertSessionHasErrors('enlace');
    }

    public function test_avance_solo_se_registra_con_la_tarea_en_proceso(): void
    {
        $tarea = Tarea::create(['titulo' => 'Asignada', 'descripcion' => 'Asignada', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now(), 'es_principal' => true]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.avance', $tarea), ['nota' => 'Antes de empezar'])
            ->assertSessionHas('error');

        $this->assertSame(0, $tarea->historialEstados()->count());
    }

    /**
     * El flujo completo nuevo: Asignada → En proceso → Completada → Autorizada
     * → Supervisada → Cerrada. "Cerrada" solo la puede hacer quien tiene el
     * permiso de supervisar/cerrar (aquí, el supervisor).
     */
    public function test_ciclo_completo_hasta_cerrada(): void
    {
        $tarea = Tarea::create(['titulo' => 'Revisar bomba', 'descripcion' => 'Revisar bomba', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now(), 'es_principal' => true]);

        $this->iniciar($tarea);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'completada', 'nota' => 'Trabajo terminado'])
            ->assertSessionHasNoErrors();
        $this->assertSame('completada', $tarea->refresh()->estado);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'autorizada', 'nota' => 'Se autoriza'])
            ->assertSessionHasNoErrors();
        $this->assertSame('autorizada', $tarea->refresh()->estado);
        $this->assertNotNull($tarea->autorizada_at);
        $this->assertSame($this->supervisor->id, $tarea->autorizado_por);

        // Supervisar es exclusivo del auditor: el supervisor ya no puede.
        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'supervisada', 'nota' => 'Intento'])
            ->assertForbidden();

        $this->actingAs($this->auditor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'supervisada', 'nota' => 'Se supervisa en sitio'])
            ->assertSessionHasNoErrors();
        $this->assertSame('supervisada', $tarea->refresh()->estado);
        $this->assertSame($this->auditor->id, $tarea->supervisor_id);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'cerrada', 'nota' => 'Conforme, se cierra'])
            ->assertSessionHasNoErrors();
        $this->assertSame('cerrada', $tarea->refresh()->estado);
        $this->assertNotNull($tarea->cerrada_at);
    }

    /**
     * Si al supervisar no se está de acuerdo, se regresa a "autorizada" — sin
     * borrar ninguna de las notas ya registradas — y se vuelve a avanzar con
     * una nueva nota que documenta la corrección.
     */
    public function test_supervisar_puede_regresar_a_autorizada_sin_borrar_el_historial(): void
    {
        $tarea = Tarea::create(['titulo' => 'Cambiar balero', 'descripcion' => 'Cambiar balero', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now(), 'es_principal' => true]);
        $this->iniciar($tarea);

        $this->actingAs($this->supervisor)->post(route('tareas.transicion', $tarea), ['estado' => 'completada', 'nota' => 'Trabajo terminado']);
        $this->actingAs($this->supervisor)->post(route('tareas.transicion', $tarea), ['estado' => 'autorizada', 'nota' => 'Primera autorización']);
        $this->actingAs($this->auditor)->post(route('tareas.transicion', $tarea), ['estado' => 'supervisada', 'nota' => 'Algo no está bien']);

        // Regresar a autorizada también es del auditor, no del supervisor.
        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'autorizada', 'nota' => 'Intento'])
            ->assertForbidden();

        // El auditor no está de acuerdo: regresa a autorizada.
        $this->actingAs($this->auditor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'autorizada', 'nota' => 'Falta corregir el acabado'])
            ->assertSessionHasNoErrors();
        $this->assertSame('autorizada', $tarea->refresh()->estado);

        // Se corrige y se vuelve a avanzar con una nota nueva.
        $this->actingAs($this->auditor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'supervisada', 'nota' => 'Ya corregido, segunda vuelta'])
            ->assertSessionHasNoErrors();
        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'cerrada', 'nota' => 'Conforme ahora sí'])
            ->assertSessionHasNoErrors();
        $this->assertSame('cerrada', $tarea->refresh()->estado);

        // Nada del historial se borró: quedan las 7 vueltas completas, con las
        // notas de la primera revisión Y de la corrección, en orden.
        $notas = $tarea->historialEstados()->orderBy('cambiado_at')->pluck('nota')->all();
        $this->assertSame([
            'INICIO', 'TRABAJO TERMINADO', 'PRIMERA AUTORIZACIÓN',
            'ALGO NO ESTÁ BIEN', 'FALTA CORREGIR EL ACABADO', 'YA CORREGIDO, SEGUNDA VUELTA', 'CONFORME AHORA SÍ',
        ], $notas);
    }

    public function test_un_tecnico_no_puede_autorizar_ni_supervisar(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');

        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'completada']);
        $tarea->responsables()->attach($tecnico->id, ['asignado_at' => now(), 'es_principal' => true]);

        $this->actingAs($tecnico)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'autorizada', 'nota' => 'Intento'])
            ->assertForbidden();
    }

    public function test_supervisor_y_auditor_ven_cualquier_tarea_aunque_no_sea_suya(): void
    {
        $otroSupervisor = Usuario::factory()->create();
        $otroSupervisor->assignRole('supervisor');
        $auditor = Usuario::factory()->create();
        $auditor->assignRole('auditor');

        // La crea OTRO supervisor; este no la creó ni está asignado.
        $tarea = Tarea::create(['titulo' => 'Ajena', 'descripcion' => 'Ajena', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada', 'creado_por' => $otroSupervisor->id]);

        $this->actingAs($this->supervisor)->get(route('tareas.show', $tarea))->assertOk();
        $this->actingAs($auditor)->get(route('tareas.show', $tarea))->assertOk();
        $this->actingAs($auditor)->get(route('tareas.index'))
            ->assertInertia(fn ($p) => $p->where('tareas.total', 1));
    }

    public function test_un_tecnico_ajeno_sigue_sin_poder_ver_una_tarea_que_no_es_suya(): void
    {
        $tecnico = Usuario::factory()->create();
        $tecnico->assignRole('tecnico');
        $otro = Usuario::factory()->create();
        $otro->assignRole('tecnico');

        $tarea = Tarea::create(['titulo' => 'Ajena', 'descripcion' => 'Ajena', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($otro->id, ['asignado_at' => now()]);

        $this->actingAs($tecnico)->get(route('tareas.show', $tarea))->assertForbidden();
    }

    public function test_supervisor_puede_comentar_un_movimiento_y_solo_lo_ven_el_y_los_responsables(): void
    {
        $responsable = Usuario::factory()->create();
        $responsable->assignRole('tecnico');
        $tecnicoAjeno = Usuario::factory()->create();
        $tecnicoAjeno->assignRole('tecnico');
        $basico = Usuario::factory()->create();
        $basico->assignRole('usuario_basico');

        $tarea = Tarea::create(['titulo' => 'Revisar', 'descripcion' => 'Revisar', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada', 'creado_por' => $basico->id]);
        $tarea->responsables()->attach($responsable->id, ['asignado_at' => now(), 'es_principal' => true]);
        $movimiento = $tarea->historialEstados()->create([
            'estado_origen' => null, 'estado_destino' => 'asignada', 'cambiado_por' => $responsable->id,
            'nota' => 'Ya empecé', 'cambiado_at' => now(),
        ]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.comentarios.store', [$tarea, $movimiento]), ['cuerpo' => 'Falta evidencia del avance.'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('comentarios_tarea', [
            'tarea_id' => $tarea->id, 'historial_estado_tarea_id' => $movimiento->id, 'cuerpo' => 'FALTA EVIDENCIA DEL AVANCE.',
        ]);

        // El responsable lo ve, dentro del mismo movimiento.
        $this->actingAs($responsable)
            ->get(route('tareas.show', $tarea))
            ->assertInertia(fn ($p) => $p
                ->where("comentariosPorMovimiento.{$movimiento->id}.0.cuerpo", 'FALTA EVIDENCIA DEL AVANCE.')
                ->where('puedeComentar', false));

        // El propio supervisor también lo ve, y puede seguir escribiendo.
        $this->actingAs($this->supervisor)
            ->get(route('tareas.show', $tarea))
            ->assertInertia(fn ($p) => $p->has("comentariosPorMovimiento.{$movimiento->id}", 1)->where('puedeComentar', true));

        // Un técnico ajeno (no responsable de esta tarea) no puede ni abrirla.
        $this->actingAs($tecnicoAjeno)->get(route('tareas.show', $tarea))->assertForbidden();

        // El usuario básico que la creó tampoco ve los comentarios: no es responsable.
        $this->actingAs($basico)
            ->get(route('tareas.show', $tarea))
            ->assertInertia(fn ($p) => $p->where('comentariosPorMovimiento', [])->where('puedeComentar', false));

        // Un técnico no puede escribir comentarios de supervisión.
        $this->actingAs($responsable)
            ->post(route('tareas.comentarios.store', [$tarea, $movimiento]), ['cuerpo' => 'Intento'])
            ->assertForbidden();
    }

    public function test_no_se_puede_comentar_un_movimiento_de_otra_tarea(): void
    {
        $tareaA = Tarea::create(['titulo' => 'A', 'descripcion' => 'A', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tareaB = Tarea::create(['titulo' => 'B', 'descripcion' => 'B', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $movimientoDeB = $tareaB->historialEstados()->create([
            'estado_origen' => null, 'estado_destino' => 'asignada', 'cambiado_por' => $this->supervisor->id, 'cambiado_at' => now(),
        ]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.comentarios.store', [$tareaA, $movimientoDeB]), ['cuerpo' => 'Intento cruzado'])
            ->assertNotFound();
    }

    public function test_no_se_puede_comentar_una_tarea_ya_cerrada(): void
    {
        $tarea = Tarea::create(['titulo' => 'Cerrada', 'descripcion' => 'Cerrada', 'fecha_limite' => now()->addDay(), 'estado' => 'cerrada']);
        $movimiento = $tarea->historialEstados()->create([
            'estado_origen' => 'supervisada', 'estado_destino' => 'cerrada', 'cambiado_por' => $this->supervisor->id, 'cambiado_at' => now(),
        ]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.comentarios.store', [$tarea, $movimiento]), ['cuerpo' => 'Demasiado tarde'])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('comentarios_tarea', ['historial_estado_tarea_id' => $movimiento->id]);
    }

    public function test_filtro_por_supervisar_muestra_completadas_y_autorizadas(): void
    {
        $completada = Tarea::create(['titulo' => 'C', 'descripcion' => 'C', 'fecha_limite' => now()->addDay(), 'estado' => 'completada']);
        $autorizada = Tarea::create(['titulo' => 'A', 'descripcion' => 'A', 'fecha_limite' => now()->addDay(), 'estado' => 'autorizada']);
        Tarea::create(['titulo' => 'EP', 'descripcion' => 'EP', 'fecha_limite' => now()->addDay(), 'estado' => 'en_proceso']);
        Tarea::create(['titulo' => 'S', 'descripcion' => 'S', 'fecha_limite' => now()->addDay(), 'estado' => 'supervisada']);

        $this->actingAs($this->supervisor)
            ->get(route('tareas.index', ['por_supervisar' => 1]))
            ->assertInertia(fn ($p) => $p
                ->where('tareas.total', 2)
                ->where('kpis.por_supervisar', 2));
    }

    public function test_el_filtro_de_estado_filtra_de_verdad(): void
    {
        Tarea::create(['titulo' => 'A', 'descripcion' => 'A', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        Tarea::create(['titulo' => 'B', 'descripcion' => 'B', 'fecha_limite' => now()->addDay(), 'estado' => 'en_proceso']);
        Tarea::create(['titulo' => 'C', 'descripcion' => 'C', 'fecha_limite' => now()->addDay(), 'estado' => 'en_proceso']);

        $this->actingAs($this->supervisor)
            ->get(route('tareas.index', ['estado' => 'en_proceso']))
            ->assertInertia(fn ($p) => $p
                ->where('tareas.total', 2)
                ->where('tareas.data.0.estado', 'en_proceso')
                ->where('tareas.data.1.estado', 'en_proceso'));
    }

    public function test_el_filtro_de_estado_distingue_cerrada_de_cancelada_en_completadas(): void
    {
        Tarea::create(['titulo' => 'Cerrada', 'descripcion' => 'Cerrada', 'fecha_limite' => now()->addDay(), 'estado' => 'cerrada']);
        Tarea::create(['titulo' => 'Cancelada', 'descripcion' => 'Cancelada', 'fecha_limite' => now()->addDay(), 'estado' => 'cancelada']);

        $this->actingAs($this->supervisor)
            ->get(route('tareas.index', ['vista' => 'completadas', 'estado' => 'cancelada']))
            ->assertInertia(fn ($p) => $p->where('tareas.total', 1)->where('tareas.data.0.estado', 'cancelada'));
    }

    public function test_la_columna_estado_se_puede_ordenar(): void
    {
        Tarea::create(['titulo' => 'A', 'descripcion' => 'A', 'fecha_limite' => now()->addDay(), 'estado' => 'en_proceso']);
        Tarea::create(['titulo' => 'B', 'descripcion' => 'B', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);

        $this->actingAs($this->supervisor)
            ->get(route('tareas.index', ['orden' => 'estado', 'dir' => 'asc']))
            ->assertInertia(fn ($p) => $p
                ->where('tareas.data.0.estado', 'asignada')
                ->where('tareas.data.1.estado', 'en_proceso'));
    }

    public function test_solo_el_auditor_puede_reprogramar(): void
    {
        $tarea = Tarea::create(['titulo' => 'x', 'descripcion' => 'x', 'fecha_limite' => now()->addDay(), 'estado' => 'asignada']);
        $tarea->responsables()->attach($this->supervisor->id, ['asignado_at' => now(), 'es_principal' => true]);

        $this->actingAs($this->supervisor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'reprogramada', 'nota' => 'Intento'])
            ->assertForbidden();

        $this->actingAs($this->auditor)
            ->post(route('tareas.transicion', $tarea), ['estado' => 'reprogramada', 'nota' => 'Se mueve a la otra semana'])
            ->assertSessionHasNoErrors();
        $this->assertSame('reprogramada', $tarea->refresh()->estado);
    }

    public function test_el_auditor_tiene_todo_lo_del_supervisor_y_mas(): void
    {
        $permisosSupervisor = $this->supervisor->getAllPermissions()->pluck('name');
        $permisosAuditor = $this->auditor->getAllPermissions()->pluck('name');

        $this->assertEmpty($permisosSupervisor->diff($permisosAuditor)->all());
        $this->assertTrue($this->auditor->can('tareas.supervisar'));
        $this->assertTrue($this->auditor->can('auditoria.exportar'));
        $this->assertFalse($this->supervisor->can('tareas.supervisar'));
        $this->assertFalse($this->auditor->can('usuarios.crear'));
    }
}
