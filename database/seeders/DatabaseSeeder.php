<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Primero, los catálogos básicos
            RolSeeder::class,
            // PermisoSeeder::class, // Descomenta cuando lo crees
            AreaSeeder::class,

            // Luego, los que dependen de los anteriores
            // DepartamentoSeeder::class, // Descomenta cuando lo crees
            UsuarioSeeder::class,
        ]);
    }
}
