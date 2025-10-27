<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\cucopClave;
use Carbon\Carbon;

class CucopClaveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $path = database_path('seeders/catalogoCucop.csv');
        $csv = array_map('str_getcsv', file($path));
        array_shift($csv);


        foreach ($csv as $row) {
            cucopClave::create([
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
            ]);
        }



    }
}
