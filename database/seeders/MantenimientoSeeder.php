<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MantenimientoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('mantenimientos')->insert([
            [
                'mantenimiento_id_bien' => 2,
                'mantenimiento_tipo' => 'Preventivo',
                'mantenimiento_observaciones' => 'Limpieza de ventiladores y cambio de pasta térmica.',
                'mantenimiento_estado' => 'Programado',
                'fecha_programada' => Carbon::now()->addWeeks(2),
                'fecha_completado' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}