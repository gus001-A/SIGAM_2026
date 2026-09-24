<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Notificaciones diarias de preventivos y garantías (§12).
Schedule::command('sigam:notificar-preventivos')->dailyAt('07:00');

// Alerta diaria de tareas vencidas (Propuesta técnica — anexo "TAREAS").
Schedule::command('sigam:notificar-tareas-vencidas')->dailyAt('07:15');
