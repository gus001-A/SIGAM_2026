<?php

namespace App\Console\Commands;

use App\Models\Tarea;
use App\Support\Notificaciones;
use Illuminate\Console\Command;

/**
 * Alerta automática de tareas próximas a vencer o ya vencidas — Propuesta
 * técnica SIGAM, anexo "TAREAS" §Circuito de alertas: el aviso no puede
 * depender de que alguien entre al sistema, así que corre en segundo plano
 * (ver `routes/console.php`). Solo se notifica a los responsables asignados
 * — sin difundir a roles, para no salirse del alcance de "asignado a mí".
 */
class NotificarTareasVencidas extends Command
{
    protected $signature = 'sigam:notificar-tareas-vencidas {--dias-aviso=2 : Días de anticipación para avisar que está próxima a vencer}';

    protected $description = 'Notifica a los responsables tareas próximas a vencer o ya vencidas';

    public function handle(): int
    {
        $diasAviso = (int) $this->option('dias-aviso');

        $tareas = Tarea::query()
            ->with(['responsables' => fn ($q) => $q->wherePivotNull('desasignado_at')])
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->whereDate('fecha_limite', '<=', today()->addDays($diasAviso))
            ->get();

        $n = 0;
        foreach ($tareas as $tarea) {
            $vencida = $tarea->fecha_limite->isPast();
            $tipo = $vencida ? 'tarea_vencida' : 'tarea_proxima';
            $titulo = ($vencida ? 'Tarea vencida: ' : 'Tarea próxima a vencer: ').$tarea->titulo;
            $cuerpo = 'Fecha límite: '.$tarea->fecha_limite->format('d/m/Y');
            $datos = ['ref' => "{$tipo}:{$tarea->id}", 'url' => route('tareas.show', $tarea->id)];

            foreach ($tarea->responsables->pluck('id') as $usuarioId) {
                Notificaciones::crear($usuarioId, $tipo, $titulo, $cuerpo, $datos);
                $n++;
            }
        }

        $this->info("Tareas notificadas: {$n}");

        return self::SUCCESS;
    }
}
