<?php

namespace App\Console\Commands;

use App\Models\PlanMantenimiento;
use App\Support\Notificaciones;
use Illuminate\Console\Command;

/**
 * Genera notificaciones in-app de preventivos próximos / vencidos, solo para
 * quien tiene el plan asignado (técnico o responsable del equipo). Pensado
 * para ejecutarse a diario (§12).
 */
class NotificarPreventivos extends Command
{
    protected $signature = 'sigam:notificar-preventivos';

    protected $description = 'Notifica preventivos próximos/vencidos a quien los tiene asignados';

    public function handle(): int
    {
        $n = $this->preventivos();

        $this->info("Preventivos notificados: {$n}");

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

            foreach ($destinatarios as $usuarioId) {
                Notificaciones::crear($usuarioId, $tipo, $titulo, $cuerpo, $datos);
                $n++;
            }
        }

        return $n;
    }
}
