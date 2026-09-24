<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles y permisos base de SIGAM. Especificación v2.0 §3 y §4.
 * Los permisos son independientes del rol (slug `modulo.accion`).
 */
class RolesPermisosSeeder extends Seeder
{
    /** Acciones permitidas por módulo. */
    private const MATRIZ = [
        'dashboard' => ['ver'],
        'usuarios' => ['ver', 'crear', 'editar', 'desactivar'],
        'roles' => ['ver', 'crear', 'editar'],
        'sucursales' => ['ver', 'crear', 'editar', 'desactivar'],
        'ubicaciones' => ['ver', 'crear', 'editar', 'desactivar'],
        'equipos' => ['ver', 'crear', 'editar', 'desactivar', 'exportar'],
        'catalogos' => ['ver', 'crear', 'editar', 'desactivar'],
        'proveedores' => ['ver', 'crear', 'editar', 'desactivar'],
        'normas' => ['ver', 'crear', 'editar', 'desactivar'],
        'formatos' => ['ver', 'crear', 'editar', 'desactivar'],
        'solicitudes' => ['ver', 'crear', 'editar', 'asignar'],
        'mantenimientos' => ['ver', 'crear', 'editar', 'asignar', 'supervisar', 'cerrar', 'exportar'],
        'tareas' => ['ver', 'crear', 'editar', 'asignar', 'cerrar', 'desactivar'],
        'documentos' => ['ver', 'crear', 'desactivar'],
        'reportes' => ['ver', 'exportar'],
        'auditoria' => ['ver', 'exportar'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. Crear todos los permisos.
        $todos = [];
        foreach (self::MATRIZ as $modulo => $acciones) {
            foreach ($acciones as $accion) {
                $slug = "{$modulo}.{$accion}";
                Permission::firstOrCreate(['name' => $slug, 'guard_name' => 'web']);
                $todos[] = $slug;
            }
        }

        // 2. Definir los permisos de cada rol.
        $soloVer = fn (array $modulos) => array_map(fn ($m) => "{$m}.ver", $modulos);

        $roles = [
            'superadministrador' => $todos,

            'supervisor' => array_merge(
                $this->modulo('dashboard'),
                $soloVer(['usuarios', 'roles']),
                ['sucursales.ver', 'sucursales.editar'],
                $this->modulo('ubicaciones'),
                $this->modulo('equipos'),
                $this->modulo('catalogos'),
                $this->modulo('proveedores'),
                $this->modulo('normas'),
                $this->modulo('formatos'),
                ['solicitudes.ver', 'solicitudes.crear', 'solicitudes.editar', 'solicitudes.asignar'],
                $this->modulo('mantenimientos'),
                $this->modulo('tareas'),
                $this->modulo('documentos'),
                $this->modulo('reportes'),
                ['auditoria.ver'],
            ),

            'tecnico' => [
                'dashboard.ver',
                'equipos.ver',
                'normas.ver',
                'formatos.ver',
                'solicitudes.ver',
                'mantenimientos.ver', 'mantenimientos.editar',
                'tareas.ver', 'tareas.editar', 'tareas.cerrar',
                'documentos.ver', 'documentos.crear',
                'reportes.ver',
            ],

            'usuario_basico' => [
                'dashboard.ver',
                'equipos.ver',
                'solicitudes.ver', 'solicitudes.crear',
                'mantenimientos.ver',
                'tareas.ver',
                'documentos.ver', 'documentos.crear',
                'reportes.ver',
            ],

            'auditor' => array_merge(
                $soloVer(array_keys(self::MATRIZ)),
                ['auditoria.exportar', 'reportes.exportar'],
            ),
        ];

        // 3. Crear roles y sincronizar permisos.
        foreach ($roles as $nombre => $permisos) {
            $rol = Role::firstOrCreate(['name' => $nombre, 'guard_name' => 'web']);
            $rol->syncPermissions(array_values(array_unique(Arr::flatten($permisos))));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** Todas las acciones de un módulo como slugs. */
    private function modulo(string $modulo): array
    {
        return array_map(fn ($a) => "{$modulo}.{$a}", self::MATRIZ[$modulo]);
    }
}
