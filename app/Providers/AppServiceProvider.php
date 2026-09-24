<?php

namespace App\Providers;

use App\Models\AsignacionMantenimiento;
use App\Models\HistorialEstadoMantenimiento;
use App\Models\Mantenimiento;
use App\Models\PlanMantenimiento;
use App\Models\ReprogramacionMantenimiento;
use App\Support\Auditoria;
use App\Support\Notificaciones;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Notificaciones in-app automáticas de mantenimiento y planes (§12).
     * Alcance deliberadamente acotado: solo se notifica a la persona a la
     * que le asignaron el trabajo (tarea, orden o plan), cuando se lo
     * asignan, cuando cambia algo relevante, o cuando está por vencer/se
     * reprogramó — nada de difusión general a roles.
     */
    private function notificacionesDeMantenimiento(): void
    {
        // Técnico asignado a una orden.
        AsignacionMantenimiento::created(function (AsignacionMantenimiento $asignacion): void {
            $orden = $asignacion->mantenimiento;
            if (! $orden || $asignacion->tecnico_id === Auth::id()) {
                return;
            }
            Notificaciones::crear(
                $asignacion->tecnico_id,
                'mantenimiento_asignado',
                "Nueva orden asignada: {$orden->folio}",
                'Se te asignó una orden de mantenimiento'.($this->objetivoOrden($orden) ? " para {$this->objetivoOrden($orden)}." : '.'),
                ['ref' => "orden:{$orden->id}", 'url' => route('mantenimientos.show', $orden->id)],
            );
        });

        // Cambio de estado de la orden -> a los técnicos activos (menos quien lo hizo).
        HistorialEstadoMantenimiento::created(function (HistorialEstadoMantenimiento $hist): void {
            $orden = $hist->mantenimiento;
            if (! $orden) {
                return;
            }
            $estado = $hist->estadoDestino?->nombre ?? $hist->estadoDestino?->clave;
            $this->notificarTecnicosDeOrden(
                $orden,
                'mantenimiento_modificado',
                "Orden actualizada: {$orden->folio}",
                'Cambió de estado'.($estado ? " a «{$estado}»" : '').'.',
                $hist->cambiado_por,
            );
        });

        // Captura de diagnóstico/trabajo/costos -> a los técnicos activos.
        // El cambio de estado y la reprogramación ya se notifican en sus
        // propios listeners (arriba/abajo), así que aquí se excluyen para no
        // duplicar el aviso.
        Mantenimiento::updated(function (Mantenimiento $orden): void {
            if (Auth::guest()) {
                return;
            }
            $cambios = $orden->getChanges();
            unset($cambios['updated_at']);
            if ($cambios === [] || array_intersect(array_keys($cambios), ['estado_id', 'programado_inicio', 'programado_fin', 'supervisor_id'])) {
                return;
            }
            $this->notificarTecnicosDeOrden(
                $orden,
                'mantenimiento_modificado',
                "Orden actualizada: {$orden->folio}",
                'Se actualizó el diagnóstico o la captura de trabajo.',
                Auth::id(),
            );
        });

        // Reprogramación -> a los técnicos activos.
        ReprogramacionMantenimiento::created(function (ReprogramacionMantenimiento $reprog): void {
            $orden = $reprog->mantenimiento;
            if (! $orden) {
                return;
            }
            $this->notificarTecnicosDeOrden(
                $orden,
                'mantenimiento_reprogramado',
                "Orden reprogramada: {$orden->folio}",
                'Nueva fecha: '.optional($reprog->inicio_nuevo)->format('d/m/Y H:i').'.',
                $reprog->reprogramado_por,
            );
        });

        // Nuevo plan de mantenimiento preventivo -> solo al técnico asignado.
        PlanMantenimiento::created(function (PlanMantenimiento $plan): void {
            if (! $plan->tecnico_id || $plan->tecnico_id === Auth::id()) {
                return;
            }
            $destino = $plan->equipo
                ? "el equipo {$plan->equipo->codigo_activo}"
                : ($plan->ubicacion ? "la instalación {$plan->ubicacion->nombre}" : 'un elemento del inventario');
            $cuerpo = "Se te asignó un mantenimiento preventivo para {$destino}, próxima fecha: "
                .(optional($plan->proxima_fecha)->format('d/m/Y') ?? 'sin definir').'.';

            Notificaciones::crear(
                $plan->tecnico_id,
                'plan_asignado',
                'Nuevo plan preventivo: '.($plan->nombre ?: $destino),
                $cuerpo,
                ['ref' => "plan:{$plan->id}", 'url' => route('planes.show', $plan->id)],
            );
        });

        // Plan modificado (reasignación de técnico, cambio de frecuencia, etc.) -> al técnico actual.
        PlanMantenimiento::updated(function (PlanMantenimiento $plan): void {
            if (! $plan->tecnico_id || $plan->tecnico_id === Auth::id() || Auth::guest()) {
                return;
            }
            $cambios = $plan->getChanges();
            unset($cambios['updated_at'], $cambios['proxima_fecha']);
            if ($cambios === []) {
                return;
            }
            Notificaciones::crear(
                $plan->tecnico_id,
                'plan_modificado',
                'Plan preventivo modificado: '.($plan->nombre ?: "plan #{$plan->id}"),
                'Se actualizaron los datos de un plan preventivo que tienes asignado.',
                ['ref' => "plan-mod:{$plan->id}:".now()->timestamp, 'url' => route('planes.show', $plan->id)],
            );
        });
    }

    /** Notifica a los técnicos con asignación activa en la orden, salvo a quien hizo el cambio. */
    private function notificarTecnicosDeOrden(Mantenimiento $orden, string $tipo, string $titulo, string $cuerpo, ?int $exceptoUsuarioId): void
    {
        $datos = ['ref' => "{$tipo}:{$orden->id}:".now()->timestamp, 'url' => route('mantenimientos.show', $orden->id)];

        $orden->asignaciones()
            ->whereNull('desasignado_at')
            ->where('tecnico_id', '!=', $exceptoUsuarioId ?? 0)
            ->pluck('tecnico_id')
            ->unique()
            ->each(fn ($tecnicoId) => Notificaciones::crear($tecnicoId, $tipo, $titulo, $cuerpo, $datos));
    }

    /** Texto corto ("el equipo EQ-001" / "la instalación Quirófano 1") para los mensajes de notificación. */
    private function objetivoOrden(Mantenimiento $orden): ?string
    {
        if ($orden->equipo) {
            return "el equipo {$orden->equipo->codigo_activo}";
        }
        if ($orden->ubicacion) {
            return "la instalación {$orden->ubicacion->nombre}";
        }

        return null;
    }
}
