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
            EdificioSeeder::class,
            UsuarioSeeder::class,

            AreaSeeder::class, // (Depende de ResguardanteSeeder y EdificioSeeder)
            DepartamentoSeeder::class, // (Depende de AreaSeeder)
            DepartamentoSeeder::class,

            BienSeeder::class,
            TraspasoSeeder::class,
            MovimientoBienSeeder::class,
            MantenimientoSeeder::class,
        ]);
    }
}
