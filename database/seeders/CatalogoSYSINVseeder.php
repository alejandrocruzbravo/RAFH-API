<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Bien;
// use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CatalogoSYSINVseeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $path = database_path('seeders/data/catalogo_bienes_v0.csv');
        $handle = fopen($path, 'r');
        $batchSize = 500;
        $batch = [];
        $header = fgetcsv($handle, 0, ',');
        $duplicateRecords = [];
        while (($row = fgetcsv($handle, 0, ',')) !== false) {

            if($row[0] === '#Eliminado')continue;

            $fechaRegistro = !empty($row[7]) ? Carbon::createFromFormat('d-M-y',$row[7]) : null;
            $bien_codigo = Bien::generarCodigo($row[11], $row[9], $fechaRegistro);


            $batch[] = [
                        'bien_codigo' => trim($bien_codigo),
                        'id_oficina' => 9,
                        'bien_estado' => 'Activo',
                        'bien_marca' => $row[0]=='' ? 'SIN MARCA':$row[0],
                        'bien_modelo' =>$row[1] === '' ? 'SIN MODELO' : $row[1],
                        'bien_serie' => $row[2],
                        'bien_descripcion' => $row[3],
                        'bien_caracteristicas' => $row[4].$row[5],
                        'bien_tipo_adquisicion' => $row[6],
                        'bien_fecha_alta' => $fechaRegistro,
                        'bien_valor_monetario' => $row[8],
                        'bien_clave' => 'I' . $row[9],
                        'bien_y' => $row[10],
                        'bien_secuencia' => $row[11],
                        'bien_provedor' => $row[12]==''?'SIN PROVEDOR':$row[12],
                        'bien_numero_factura' => $row[13]==''?'0':$row[13],
                        'bien_ubicacion_actual' => 9
                        ];

                if (count($batch) > 0) {
                    
                    // 1. Obtener todos los códigos únicos del batch
                    $batchCodes = collect($batch)->pluck('bien_codigo')->unique()->all();

                    // 2. Consultar la base de datos para ver cuáles ya existen
                    $existingCodes = DB::table('bienes')
                        ->whereIn('bien_codigo', $batchCodes)
                        ->pluck('bien_codigo') 
                        ->all();

                    // 3. Crear un mapa rápido de los códigos existentes
                    $existingCodesMap = array_flip($existingCodes);
                    
                    $newRecords = [];
                    

                    // 4. Iterar sobre el batch original y separar
                    foreach ($batch as $record) {
                        if (array_key_exists($record['bien_codigo'], $existingCodesMap)) {
                            // Este registro ya existe en la DB -> ES UN DUPLICADO
                            $duplicateRecords[] = $record;
                        } else {
                            // Este registro es nuevo
                            $newRecords[] = $record;
                        }
                    }

                    // 5. Insertar solo los registros nuevos (si los hay)
                    if (count($newRecords) > 0) {
                        // Usar insert() o insertOrIgnore() si te preocupan duplicados internos del batch.
                        DB::table('bienes')->insert($newRecords); 
                    }
                                
                                // Si necesitas manejar los duplicados omitidos, puedes acceder a $existingCodes aquí.

                                // Restablecer el batch (si este código está fuera del bucle final)
                                $batch = [];
                            

            // if (count($batch) === $batchSize) {
            //     DB::table('bienes')->insert($batch);
            //     $batch = [];
            // }
                }

        }
        if (!empty($batch)) {
            DB::table('bienes')->insert($batch);

        }

        fclose($handle);
        // $text = implode("\n",$duplicateRecords);
        echo count($duplicateRecords);
        
File::put(
    storage_path('app/private/duplicateRecords.json'),
    json_encode($duplicateRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);


    }
}
