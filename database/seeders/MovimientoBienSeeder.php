<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MovimientoBienSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('movimientos_bien')->insert([
            [
                'movimiento_id_bien' => 1,  // Corregido (Bien: Laptop Dell)
                'movimiento_id_dep' => 2,   // Añadido (Depto. TI)
                'movimiento_fecha' => Carbon::now()->subDays(10),
                'movimiento_tipo' => 'Registro',
                'movimiento_id_usuario_origen' => 1,
                'movimiento_id_usuario_destino' => 2,
                'movimiento_id_usuario_autorizado' => 1, // Corregido
                'movimiento_observaciones' => 'Registro inicial del equipo en el sistema.',
            ],
            [
                'movimiento_id_bien' => 2,  // Corregido (Bien: Monitor Samsung)
                'movimiento_id_dep' => 1,   // Añadido (Depto. Contabilidad)
                'movimiento_fecha' => Carbon::now()->subDays(5),
                'movimiento_tipo' => 'Transferencia',
                'movimiento_id_usuario_origen' => 2,
                'movimiento_id_usuario_destino' => 1,
                'movimiento_id_usuario_autorizado' => 1, // Corregido
                'movimiento_observaciones' => 'Cambio de área del resguardante.',
            ],
            [
                'movimiento_id_bien' => 2,  // Corregido (Bien: Silla)
                'movimiento_id_dep' => 2,   // Añadido (Depto. Servicios Escolares)
                'movimiento_fecha' => Carbon::now()->subDays(2),
                'movimiento_tipo' => 'Mantenimiento',
                'movimiento_id_usuario_origen' => 2,
                'movimiento_id_usuario_destino' => 1,
                'movimiento_id_usuario_autorizado' => 1, // Corregido
                'movimiento_observaciones' => 'Reparación de pata rota.',
            ]
        ]);
    }
}