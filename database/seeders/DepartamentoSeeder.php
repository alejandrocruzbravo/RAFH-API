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
                'dep_resposable' => 'Juan Pérez',
                'dep_correo_institucional' => 'contabilidad@instituto.com',
                'id_area' => 1, // Pertenece al Área 'FIN' (ID 1)
            ],
            [
                'dep_nombre' => 'Soporte Técnico',
                'dep_description' => 'Departamento de Soporte de TI',
                'dep_resposable' => 'Ana López',
                'dep_correo_institucional' => 'soporte@instituto.com',
                'id_area' => 2, // Pertenece al Área 'TEC' (ID 2)
            ],
            [
                'dep_nombre' => 'Servicios Escolares',
                'dep_description' => 'Departamento de gestión escolar y estudiantil',
                'dep_resposable' => 'María García',
                'dep_correo_institucional' => 'escolares@instituto.com',
                'id_area' => 1, // Pertenece al Área 'FIN' (ID 1)
            ]
        ]);
    }
}