<?php

namespace App\Providers;

use App\Models\AsignacionMantenimiento;
use App\Models\HistorialEstadoMantenimiento;
use App\Models\Mantenimiento;
use App\Support\Auditoria;
use App\Support\Notificaciones;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $this->auditoriaDeAutenticacion();
        $this->notificacionesDeMantenimiento();
    }

    /** Bitácora de auditoría: acceso y salida del sistema (§13). */
    private function auditoriaDeAutenticacion(): void
    {
        Event::listen(Login::class, function (Login $evento): void {
            $evento->user->forceFill(['ultimo_acceso_at' => now()])->saveQuietly();
            Auditoria::registrar('acceso', 'autenticacion');
        });

        Event::listen(Logout::class, function (Logout $evento): void {
            if ($evento->user) {
                Auditoria::registrar('salida', 'autenticacion', usuario: $evento->user);
            }
        });
    }

    /** Notificaciones in-app automáticas de mantenimiento (§12). */
    private function notificacionesDeMantenimiento(): void
    {
        // Técnico asignado a una orden.
        AsignacionMantenimiento::created(function (AsignacionMantenimiento $asignacion): void {
            $orden = $asignacion->mantenimiento;
            if (! $orden) {
                return;
            }
            Notificaciones::crear(
                $asignacion->tecnico_id,
                'asignacion',
                "Nueva asignación: {$orden->folio}",
                'Se te asignó una orden de mantenimiento'.($orden->equipo ? " para el equipo {$orden->equipo->codigo_activo}." : '.'),
                ['ref' => "orden:{$orden->id}", 'url' => route('mantenimientos.show', $orden->id)],
            );
        });

        // Orden urgente / crítica recién creada -> supervisores.
        Mantenimiento::created(function (Mantenimiento $orden): void {
            $clave = $orden->prioridad?->clave;
            if (! in_array($clave, ['urgente', 'critica'], true)) {
                return;
            }
            Notificaciones::paraRoles(
                ['supervisor', 'superadministrador'],
                'urgencia',
                "Orden {$clave}: {$orden->folio}",
                $orden->equipo ? "Equipo {$orden->equipo->codigo_activo} — {$orden->problema_reportado}" : $orden->problema_reportado,
                ['ref' => "orden:{$orden->id}", 'url' => route('mantenimientos.show', $orden->id)],
                exceptoUsuarioId: $orden->creado_por,
            );
        });

        // Cambio de estado de la orden.
        HistorialEstadoMantenimiento::created(function (HistorialEstadoMantenimiento $hist): void {
            $clave = $hist->estadoDestino?->clave;
            $orden = $hist->mantenimiento;
            if (! $orden) {
                return;
            }

            if ($clave === 'realizado') {
                Notificaciones::paraRoles(
                    ['supervisor', 'superadministrador'],
                    'pendiente_supervision',
                    "Orden lista para supervisar: {$orden->folio}",
                    $orden->equipo ? "El trabajo del equipo {$orden->equipo->codigo_activo} está terminado y espera revisión." : null,
                    ['ref' => "orden:{$orden->id}", 'url' => route('mantenimientos.show', $orden->id)],
                );
            }

            if ($clave === 'cerrado' && $orden->creado_por) {
                Notificaciones::crear(
                    $orden->creado_por,
                    'trabajo_terminado',
                    "Orden cerrada: {$orden->folio}",
                    'La orden de mantenimiento que solicitaste fue completada y cerrada.',
                    ['ref' => "orden:{$orden->id}", 'url' => route('mantenimientos.show', $orden->id)],
                );
            }
        });
    }
}
