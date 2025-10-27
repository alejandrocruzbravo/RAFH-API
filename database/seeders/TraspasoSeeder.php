<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // Importa la clase Carbon para manejar fechas

class TraspasoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('traspasos')->insert([
            [
                // TRASPASO COMPLETADO (para widget "Última transferencia")
                'traspaso_id_bien' => 1, // Asegúrate de que el bien con ID 1 exista
                'traspaso_id_usuario_origen' => 1, // ID del usuario que envía
                'traspaso_id_usuario_destino' => 2, // ID del usuario que recibe
                'traspaso_fecha_solicitud' => Carbon::now()->subDays(5), // Hace 5 días
                'traspaso_estado' => 'Completado', // ¡Estado importante!
                'traspaso_observaciones' => 'Traspaso de equipo de cómputo a nuevo ingreso.',
            ],
            [
                // TRASPASO PENDIENTE (para widget "Notificaciones")
                'traspaso_id_bien' => 2, // Asegúrate de que el bien con ID 2 exista
                'traspaso_id_usuario_origen' => 2, // ID del usuario que envía
                'traspaso_id_usuario_destino' => 1, // ID del usuario que recibe (ej. Admin)
                'traspaso_fecha_solicitud' => Carbon::now()->subDay(), // Ayer
                'traspaso_estado' => 'Pendiente', // ¡Estado importante!
                'traspaso_observaciones' => 'Solicitud de cambio de monitor por falla.',
            ]
        ]);
    }
}