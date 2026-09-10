<?php

namespace App\Console\Commands;

use App\Models\Equipo;
use App\Models\PlanMantenimiento;
use App\Support\Notificaciones;
use Illuminate\Console\Command;

/**
 * Genera notificaciones in-app de preventivos próximos / vencidos y garantías
 * por vencer. Pensado para ejecutarse a diario (§12).
 */
class NotificarPreventivos extends Command
{
    protected $signature = 'sigam:notificar-preventivos {--dias-garantia=30 : Ventana de aviso para garantías}';

    protected $description = 'Notifica preventivos próximos/vencidos y garantías por vencer';

    public function handle(): int
    {
        $proximos = $this->preventivos();
        $garantias = $this->garantias((int) $this->option('dias-garantia'));

        $this->info("Preventivos notificados: {$proximos}");
        $this->info("Garantías notificadas: {$garantias}");

        return self::SUCCESS;
    }

    private function preventivos(): int
    {
        $planes = PlanMantenimiento::query()
            ->with(['equipo:id,codigo_activo,descripcion,responsable_id', 'tecnico:id'])
            ->where('estado', 'activo')
            ->whereNotNull('proxima_fecha')
            ->whereDate('proxima_fecha', '<=', today()->addDays(30))
            ->get();

        $n = 0;
        foreach ($planes as $plan) {
            $vencido = $plan->proxima_fecha->isPast();
            $dentroDeVentana = ! $vencido
                && today()->addDays($plan->dias_aviso_anticipado)->gte($plan->proxima_fecha);

            if (! $vencido && ! $dentroDeVentana) {
                continue;
            }

            $tipo = $vencido ? 'mantenimiento_vencido' : 'mantenimiento_proximo';
            $titulo = $vencido
                ? "Preventivo VENCIDO: {$plan->equipo?->codigo_activo}"
                : "Preventivo próximo: {$plan->equipo?->codigo_activo}";
            $cuerpo = 'Fecha programada: '.$plan->proxima_fecha->format('d/m/Y')
                .($plan->nombre ? " · {$plan->nombre}" : '');
            $datos = ['ref' => "plan:{$plan->id}", 'url' => route('planes.show', $plan->id)];

            $destinatarios = collect([$plan->tecnico_id, $plan->equipo?->responsable_id])->filter()->unique();

            if ($destinatarios->isEmpty()) {
                Notificaciones::paraRoles(['supervisor', 'superadministrador'], $tipo, $titulo, $cuerpo, $datos);
                $n++;

                continue;
            }

            foreach ($destinatarios as $usuarioId) {
                Notificaciones::crear($usuarioId, $tipo, $titulo, $cuerpo, $datos);
                $n++;
            }
        }

        return $n;
    }

    private function garantias(int $dias): int
    {
        $equipos = Equipo::query()
            ->with('responsable:id')
            ->whereNotNull('garantia_hasta')
            ->whereDate('garantia_hasta', '>=', today())
            ->whereDate('garantia_hasta', '<=', today()->addDays($dias))
            ->get();

        $n = 0;
        foreach ($equipos as $equipo) {
            $titulo = "Garantía por vencer: {$equipo->codigo_activo}";
            $cuerpo = 'La garantía vence el '.$equipo->garantia_hasta->format('d/m/Y').'.';
            $datos = ['ref' => "equipo-garantia:{$equipo->id}", 'url' => route('equipos.show', $equipo->id)];

            if ($equipo->responsable_id) {
                Notificaciones::crear($equipo->responsable_id, 'garantia_por_vencer', $titulo, $cuerpo, $datos);
            } else {
                Notificaciones::paraRoles(['supervisor', 'superadministrador'], 'garantia_por_vencer', $titulo, $cuerpo, $datos);
            }
            $n++;
        }

        return $n;
    }
}
