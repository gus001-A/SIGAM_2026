<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * El auditor sube de nivel: queda por encima del supervisor (hereda todos sus
 * permisos) y por debajo del superadministrador. Supervisar y reprogramar
 * tareas pasa a ser exclusivo del auditor; el supervisor solo autoriza.
 *
 * Se hace con cambios puntuales (no re-ejecutando el seeder completo) para no
 * pisar ajustes que se hayan hecho a mano a los roles desde el sistema.
 */
return new class extends Migration
{
    private const NUEVOS = ['tareas.autorizar', 'tareas.reprogramar'];

    private const EXCLUSIVOS_AUDITOR = ['tareas.supervisar', 'tareas.reprogramar'];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([...self::NUEVOS, 'tareas.supervisar'] as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        $superadmin = Role::where('name', 'superadministrador')->where('guard_name', 'web')->first();
        $superadmin?->givePermissionTo(self::NUEVOS);

        $supervisor = Role::where('name', 'supervisor')->where('guard_name', 'web')->first();
        if ($supervisor) {
            // Quien antes podía supervisar, ahora autoriza.
            if ($supervisor->hasPermissionTo('tareas.supervisar')) {
                $supervisor->givePermissionTo('tareas.autorizar');
            }
            foreach (self::EXCLUSIVOS_AUDITOR as $permiso) {
                if ($supervisor->hasPermissionTo($permiso)) {
                    $supervisor->revokePermissionTo($permiso);
                }
            }
        }

        $auditor = Role::where('name', 'auditor')->where('guard_name', 'web')->first();
        if ($auditor) {
            $auditor->givePermissionTo(array_unique([
                ...($supervisor?->permissions->pluck('name')->all() ?? []),
                ...Permission::where('name', 'like', 'auditoria.%')->pluck('name')->all(),
                'tareas.autorizar',
                ...self::EXCLUSIVOS_AUDITOR,
            ]));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::where('name', 'supervisor')->where('guard_name', 'web')->first()
            ?->givePermissionTo('tareas.supervisar');

        Permission::whereIn('name', self::NUEVOS)->where('guard_name', 'web')->get()
            ->each(fn (Permission $p) => $p->delete());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
