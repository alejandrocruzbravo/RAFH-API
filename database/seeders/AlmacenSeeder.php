<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;
use App\Models\Departamento;
use App\Models\Edificio;
use App\Models\Oficina;

class AlmacenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear Área (002)
        $area = Area::firstOrCreate(
            ['area_codigo' => '002'], // Busca por código para no duplicar
            ['area_nombre' => 'SUBDIRECCIÓN DE SERVICIOS ADMINISTRATIVOS']
        );

        // 2. Crear Edificio (Edificio R)
        $edificio = Edificio::firstOrCreate(
            ['nombre' => 'Edificio R']
        );

        $departamento = Departamento::firstOrCreate(
            ['dep_codigo' => '005'],
            [
                'dep_nombre' => 'DEPARTAMENTO DE RECURSOS MAT. Y SERVICIOS',
                'id_area' => 1, 
            ]
        );

        Oficina::firstOrCreate(
            ['ofi_codigo' => '006'],
            [
                'nombre' => 'bIEN',
                'id_edificio' => 1,
                'id_departamento' => 1,
            ]
        );
    }
}