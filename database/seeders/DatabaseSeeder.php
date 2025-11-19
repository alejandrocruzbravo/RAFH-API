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
            UsuarioSeeder::class,
            DepartamentosSeeder::class,
            CatalogoResguardante::class,
            //AlmacenSeeder::class,
            EdificioSeeder::class,
            OrganigramaSeeder::class,
            Camba_cucopSeeder::class,
            CucopClaveSeeder::class
        ]);
    }
}
