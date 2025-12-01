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
            RolSeeder::class,
            PermisosSeeder::class,
            Roles_PermisosSeeder::class,
            UsuarioSeeder::class,
            DepartamentosSeeder::class,
            CatalogoResguardante::class,
            EdificioSeeder::class,
            OrganigramaSeeder::class,
            CatalogoResguardante::class,
            Camba_cucopSeeder::class,
            CucopClaveSeeder::class,
            CatalogoSYSINVseeder::class,
            ResguardosSeeder::class,
        ]);
    }
}
