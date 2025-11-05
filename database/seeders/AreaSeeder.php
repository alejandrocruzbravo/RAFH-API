<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('areas')->insert([
            [
                'area_codigo' => 'FIN',
                'area_nombre' => 'Finanzas',
                // Asume que el Resguardante ID 1 (Jefe) existe
                'id_resguardante_responsable' => null, 
                // Asume que el Edificio ID 1 (Edificio A) existe
                'id_edificio' => 1, 
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'area_codigo' => 'TEC',
                'area_nombre' => 'Tecnología',
                'id_resguardante_responsable' => null, // Puede ser nulo
                'id_edificio' => 2, // Edificio B
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
?>