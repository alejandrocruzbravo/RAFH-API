<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Edificio;

class EdificioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usamos una transacción para insertar todos los datos de golpe
        DB::transaction(function () {
            
            $edificios = [
                'EDIFICIO A', 'EDIFICIO B', 'EDIFICIO C', 'EDIFICIO D',
                'EDIFICIO E', 'EDIFICIO F', 'EDIFICIO G', 'EDIFICIO H',
                'EDIFICIO I', 'EDIFICIO J', 'EDIFICIO K', 'EDIFICIO L',
                'EDIFICIO M', 'EDIFICIO M', 'EDIFICIO N', 'EDIFICIO O',
                'EDIFICIO P', 'EDIFICIO Q', 'EDIFICIO R', 'EDIFICIO S',
                'EDIFICIO T', 'EDIFICIO U', 'EDIFICO U', 'EDIFICIO V',
                'EDIFICIO W', 'EDIFICIO X', 'EDIFICIO Y', 'EDIFICIO Z',
                'EDIFICIO Z', 'EDIFICIO AA', 'EDIFICIO AB', 'EDIFICIO AC',
                'DOMO DEPORTIVO (AD)',
            ];

            // Iteramos y creamos cada edificio
            // Usamos firstOrCreate para evitar duplicados si se corre de nuevo
            foreach ($edificios as $nombre) {
                Edificio::firstOrCreate(['nombre' => $nombre]);
            }
        });
    }
}