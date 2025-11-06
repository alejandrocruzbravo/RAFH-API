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
                        'tipo' => $row[0],
                        'clave_cucop' => $row[1],
                        'partida_especifica' => $row[2],
                        'clave_cucop_plus' => $row[3],
                        'descripcion' => $row[4],
                        'nivel' => $row[5],
                        'camb' => $row[6],
                        'unidad_medida' => $row[7],
                        'tipo_contratacion' => $row[8],
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
