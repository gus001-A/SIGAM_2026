<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesPermisosSeeder::class,
            CatalogosSeeder::class,
        ]);

        $matriz = Sucursal::firstOrCreate(
            ['codigo' => 'MATRIZ'],
            ['nombre' => 'Sucursal Matriz', 'estado' => 'activo'],
        );

        $admin = Usuario::firstOrCreate(
            ['email' => 'admin@sigam.test'],
            [
                'nombre' => 'Administrador',
                'apellidos' => 'SIGAM',
                'password' => Hash::make('password'),
                'sucursal_id' => $matriz->id,
                'estado' => 'activo',
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles('superadministrador');
    }
}
