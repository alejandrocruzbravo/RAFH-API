<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class Camba_cucopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        
        $path = database_path('seeders/data/camba-cucop.csv');
        $handle = fopen($path, 'r');
        $batchSize = 500;
        $batch = [];
        $header = fgetcsv($handle, 0, ',');

        while (($row = fgetcsv($handle, 0, ',')) !== false) {

            if(empty($row[6])) continue;

            
            $batch[] = [
                        'clave_cucop' => $row[1],
                        'partida_especifica' => $row[2],
                        'descripcion' => $row[4],
                        'camb' => $row[6],
                        ];

            if (count($batch) === $batchSize) {
                DB::table('catalogo_camb_cucop')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('catalogo_camb_cucop')->insert($batch);
        }

        fclose($handle);

        

    }


}
