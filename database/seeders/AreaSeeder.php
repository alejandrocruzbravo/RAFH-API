<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('areas')->insert([
            [
                'area_nombre' => 'Finanzas',
                'area_responsable' => 'Responsable de Finanzas',
                'area_edificio' => 'Edificio A'
            ],
            [
                'area_nombre' => 'Tecnología',
                'area_responsable' => 'Responsable de Tecnología',
                'area_edificio' => 'Edificio B'
            ],
        ]);
    }
}
?>