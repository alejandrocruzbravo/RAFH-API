<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // <-- Asegúrate de importar esto

class DepartamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('departamentos')->insert([
            [
                'dep_nombre' => 'Contabilidad',
                'dep_description' => 'Departamento de Contabilidad y Finanzas',
                'dep_area_codigo' => '1', // Columna corregida
                'dep_resposable' => 'Juan Pérez',
                'dep_correo_institucional' => 'contabilidad@instituto.com',
            ],
            [
                'dep_nombre' => 'Soporte Técnico',
                'dep_description' => 'Departamento de Soporte de TI',
                'dep_area_codigo' => '2', // Columna corregida
                'dep_resposable' => 'Ana López',
                'dep_correo_institucional' => 'soporte@instituto.com',
            ],
        ]);
    }
}