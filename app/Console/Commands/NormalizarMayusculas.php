<?php

namespace App\Console\Commands;

use App\Models\Concerns\ConvierteMayusculas;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Sube a MAYÚSCULAS los campos de texto libre de TODOS los registros que ya
 * existían antes de tener `ConvierteMayusculas` (o que se sembraron con un
 * valor mixto/minúsculas, como el usuario admin de los seeders) — el trait
 * solo actúa en altas/ediciones nuevas, nunca reprocesa lo ya guardado.
 *
 * Usa `saveQuietly()` a propósito: es un backfill mecánico, no una edición
 * real, así que no debe generar entradas de auditoría ni disparar las
 * notificaciones de `AppServiceProvider` (que sí escuchan `updated`).
 */
class NormalizarMayusculas extends Command
{
    protected $signature = 'sigam:normalizar-mayusculas {--dry-run : Solo cuenta cuántos registros cambiarían, sin guardar}';

    protected $description = 'Sube a mayúsculas los campos de texto libre de los modelos con ConvierteMayusculas, incluyendo registros ya existentes';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $totalModelos = 0;
        $totalRegistros = 0;

        foreach ($this->modelosConTrait() as $clase) {
            $campos = $clase::camposAMayuscular();
            if ($campos === []) {
                continue;
            }

            $query = in_array(SoftDeletes::class, class_uses_recursive($clase), true)
                ? $clase::withTrashed()
                : $clase::query();

            $tocados = 0;
            $query->chunkById(200, function ($registros) use ($campos, $dryRun, &$tocados): void {
                foreach ($registros as $registro) {
                    $cambios = [];
                    foreach ($campos as $campo) {
                        $valor = $registro->{$campo};
                        if (is_string($valor) && $valor !== '') {
                            $mayus = mb_strtoupper($valor, 'UTF-8');
                            if ($mayus !== $valor) {
                                $cambios[$campo] = $mayus;
                            }
                        }
                    }
                    if ($cambios !== []) {
                        $tocados++;
                        if (! $dryRun) {
                            $registro->forceFill($cambios)->saveQuietly();
                        }
                    }
                }
            });

            if ($tocados > 0) {
                $totalModelos++;
                $totalRegistros += $tocados;
                $this->line(($dryRun ? '[dry-run] ' : '').class_basename($clase).": {$tocados} registro(s)");
            }
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Listo — {$totalRegistros} registro(s) en {$totalModelos} modelo(s).");

        return self::SUCCESS;
    }

    /** @return list<class-string> */
    private function modelosConTrait(): array
    {
        $modelos = [];
        foreach (File::files(app_path('Models')) as $archivo) {
            $clase = 'App\\Models\\'.Str::before($archivo->getFilename(), '.php');
            if (! class_exists($clase)) {
                continue;
            }
            $reflexion = new ReflectionClass($clase);
            if ($reflexion->isAbstract() || ! in_array(ConvierteMayusculas::class, class_uses_recursive($clase), true)) {
                continue;
            }
            $modelos[] = $clase;
        }

        return $modelos;
    }
}
