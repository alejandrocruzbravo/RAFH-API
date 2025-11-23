<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartamentosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // database/seeders/DepartamentosSeeder.php
    public function run(): void
    {
        $nombres = ['TEMP-1', 'TEMP-2', 'TEMP-3', 'TEMP-4'];

        foreach ($nombres as $nombre) {
            \App\Models\Departamento::factory()->create([
                'dep_nombre' => $nombre,
                // La factory se encarga de inventar el código y lo demás automáticamente
            ]);
        }
    }
}
