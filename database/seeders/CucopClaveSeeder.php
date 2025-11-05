<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CucopClave;
use Carbon\Carbon;

class CucopClaveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $path = database_path('seeders/data/catalogoCucop.csv');
        $handle = fopen($path,'r');
        $batchSize = 500;
        $batch = [];
        $header = fgetcsv($handle, 0, ',');

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $batch[] = [
                    'cucop_plus' => $row[0],
                    'cucop' => $row[1],
                    'descripcion' => $row[2],
                    'partida_especifica' => $row[3],
                    'descripcion_partida_especifia' => $row[4],
                    'partida_generica' => $row[5],
                    'descripcion_partida_generica' => $row[6],
                    'concepto' => $row[7],
                    'descripcion_concepto' => $row[8],
                    'capitulo' => $row[9],
                    'fecha_alta_cucop' => $row[10] == '' ? null : Carbon::createFromFormat('d/m/Y',$row[10])->format('Y-m-d')
                        ];

            if (count($batch) === $batchSize) {
                DB::table('catalogo_cucop')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('catalogo_cucop')->insert($batch);
        }

        fclose($handle);


        // foreach ($csv as $row) {
        //     CucopClave::create([
        //         'cucop_plus' => $row[0],
        //         'cucop' => $row[1],
        //         'descripcion' => $row[2],
        //         'partida_especifica' => $row[3],
        //         'descripcion_partida_especifia' => $row[4],
        //         'partida_generica' => $row[5],
        //         'descripcion_partida_generica' => $row[6],
        //         'concepto' => $row[7],
        //         'descripcion_concepto' => $row[8],
        //         'capitulo' => $row[9],
        //         'fecha_alta_cucop' => $row[10] == '' ? null : Carbon::createFromFormat('d/m/Y',$row[10])->format('Y-m-d')
        //     ]);
        // }



    }
}
