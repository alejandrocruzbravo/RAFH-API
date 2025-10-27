<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BienSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('bienes')->insert([
            [
                'bien_codigo' => 'TEC-LAP-001',
                'bien_nombre' => 'Laptop Dell XPS 15',
                'bien_categoria' => 'Equipo de Cómputo',
                'bien_ubicacion_actual' => 'Oficina de TI',
                'bien_estado' => 'Activo',
                'bien_modelo' => 'XPS 15',
                'bien_marca' => 'DELL',
                'bien_fecha_adquision' => Carbon::now()->subYear(), // Hace 1 año
                'bien_valor_monetario' => 25000.00,
                'bien_id_dep' => 2, // Asegúrate de que el depto ID 2 exista
            ],
            [
                'bien_codigo' => 'ADM-MON-001',
                'bien_nombre' => 'Monitor Samsung 24"',
                'bien_categoria' => 'Equipo de Cómputo',
                'bien_ubicacion_actual' => 'Oficina de Contabilidad',
                'bien_estado' => 'Activo',
                'bien_modelo' => 'LF24T350FHLXZX',
                'bien_marca' => 'Samsung',
                'bien_fecha_adquision' => Carbon::now()->subMonths(6), // Hace 6 meses
                'bien_valor_monetario' => 4500.00,
                'bien_id_dep' => 1, // Asegúrate de que el depto ID 1 exista
            ]
        ]);
    }
}