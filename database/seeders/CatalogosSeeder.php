<?php

namespace Database\Seeders;

use App\Models\EstadoEquipo;
use App\Models\EstadoMantenimiento;
use App\Models\Prioridad;
use App\Models\TipoEquipo;
use App\Models\TipoMantenimiento;
use App\Models\TipoUbicacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Catálogos iniciales sugeridos. Propuesta SIGAM Anexo A / Especificación §5.13.
 * Son valores de arranque; el superadministrador puede ampliarlos.
 */
class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $this->tiposUbicacion();
        $this->tiposEquipo();
        $this->estadosEquipo();
        $this->tiposMantenimiento();
        $this->prioridades();
        $this->estadosMantenimiento();
    }

    private function tiposUbicacion(): void
    {
        foreach (['Área', 'Almacén', 'Anaquel', 'Repisa', 'Cuarto técnico', 'Consultorio'] as $nombre) {
            TipoUbicacion::firstOrCreate(
                ['clave' => Str::slug($nombre, '_')],
                ['nombre' => $nombre, 'estado' => 'activo'],
            );
        }
    }

    private function tiposEquipo(): void
    {
        $tipos = ['Médico', 'Cómputo', 'Climatización', 'Eléctrico', 'Hidráulico', 'Mobiliario', 'Transporte', 'Seguridad'];
        foreach ($tipos as $nombre) {
            TipoEquipo::firstOrCreate(
                ['clave' => Str::slug($nombre, '_')],
                ['nombre' => $nombre, 'estado' => 'activo'],
            );
        }
    }

    private function estadosEquipo(): void
    {
        $estados = [
            ['operativo', 'Operativo', '#16a34a', true],
            ['en_mantenimiento', 'En mantenimiento', '#f59e0b', false],
            ['fuera_de_servicio', 'Fuera de servicio', '#dc2626', false],
            ['baja', 'Baja', '#6b7280', false],
        ];
        foreach ($estados as [$clave, $nombre, $color, $operativo]) {
            EstadoEquipo::firstOrCreate(
                ['clave' => $clave],
                ['nombre' => $nombre, 'color' => $color, 'es_operativo' => $operativo, 'estado' => 'activo'],
            );
        }
    }

    private function tiposMantenimiento(): void
    {
        $tipos = [
            ['preventivo', 'Preventivo', 'preventivo'],
            ['correctivo', 'Correctivo', 'correctivo'],
            ['urgente', 'Urgente', 'urgente'],
            ['inspeccion', 'Inspección', 'inspeccion'],
        ];
        foreach ($tipos as [$clave, $nombre, $categoria]) {
            TipoMantenimiento::firstOrCreate(
                ['clave' => $clave],
                ['nombre' => $nombre, 'categoria' => $categoria, 'estado' => 'activo'],
            );
        }
    }

    private function prioridades(): void
    {
        $prioridades = [
            ['normal', 'Normal', 1, 4320, '#16a34a'],       // 3 días
            ['prioritaria', 'Prioritaria', 2, 1440, '#f59e0b'], // 24 h
            ['urgente', 'Urgente', 3, 240, '#ea580c'],       // 4 h
            ['critica', 'Crítica', 4, 60, '#dc2626'],        // 1 h
        ];
        foreach ($prioridades as [$clave, $nombre, $nivel, $minutos, $color]) {
            Prioridad::firstOrCreate(
                ['clave' => $clave],
                ['nombre' => $nombre, 'nivel' => $nivel, 'minutos_respuesta' => $minutos, 'color' => $color, 'estado' => 'activo'],
            );
        }
    }

    private function estadosMantenimiento(): void
    {
        // clave, nombre, orden, es_terminal, es_abierto
        $estados = [
            ['solicitado', 'Solicitado', 10, false, true],
            ['autorizado', 'Autorizado', 20, false, true],
            ['asignado', 'Asignado', 30, false, true],
            ['en_proceso', 'En proceso', 40, false, true],
            ['en_espera_refaccion', 'En espera de refacción', 45, false, true],
            ['realizado', 'Realizado', 50, false, true],
            ['supervisado', 'Supervisado', 60, false, true],
            ['cerrado', 'Cerrado', 70, true, false],
            ['reprogramado', 'Reprogramado', 80, false, true],
            ['cancelado', 'Cancelado', 90, true, false],
            ['fuera_de_servicio', 'Equipo fuera de servicio', 95, false, true],
        ];
        foreach ($estados as [$clave, $nombre, $orden, $terminal, $abierto]) {
            EstadoMantenimiento::firstOrCreate(
                ['clave' => $clave],
                ['nombre' => $nombre, 'orden' => $orden, 'es_terminal' => $terminal, 'es_abierto' => $abierto, 'estado' => 'activo'],
            );
        }
    }
}
