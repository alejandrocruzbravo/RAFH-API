<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoResguardante extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/testResguardantes.txt');

        if (!file_exists($path)) {
            $this->command?->warn("Archivo no encontrado: {$path}");
            return;
        }

        $batch = [];

        /**
         * Apartir de esta zona se rellena el batch para la primera insercion de datos
         * a su vez evita los duplicados del archivo que se abrio
         */
        if (($handle = fopen($path, 'r')) !== false) {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') continue;

                // Separar columnas por espacios múltiples
                $columns = preg_split('/\s+/', $line);

                if (count($columns) >= 2) {
                    $rfc = $columns[0];

                    // Construir nombre completo (resto de columnas)
                    $nombreCompleto = array_slice($columns, 1);

                    // Asignación simple: [apellido_paterno, apellido_materno, nombre...]
                    $apellidoPaterno = $nombreCompleto[0] ?? '';
                    $apellidoMaterno = $nombreCompleto[1] ?? '';
                    $nombre = implode(' ', array_slice($nombreCompleto, 2));

                    // Evitar duplicados en memoria por 'codigo'
                    if (!isset($batch[$rfc])) {
                        $batch[$rfc] = [
                            'res_nombre'=> $nombre,
                            'res_apellidos'=>$apellidoPaterno.' '.$apellidoMaterno,
                            'res_puesto'=>'SIN PUESTO',
                            'res_rfc'=> $rfc,
                            'res_curp'=>null,
                            // 'res_correo'  => $apellidoPaterno !== '' ? mb_substr($apellidoPaterno,0,3) . mb_substr($nombre,0,3).'@rafh.com' : mb_substr($nombre,0,3).'@rafh.com', // generar los correo de los resgurdantes que nos pasaron porque no tienen mas informacion que su RFC
                            'res_correo'  => fake()->unique()->safeEmail(),
                            'res_telefono'=>fake()->numerify('##########'),
                            'res_id_usuario'=>null,
                            'res_departamento'=>rand(1,4),
                            'id_oficina'=>null,
                        ];
                    }
                }
            }
            fclose($handle);
        }

        if (empty($batch)) {
            $this->command?->warn('No se encontraron registros válidos en el archivo.');
            return;
        }

        // Convertimos el mapa a un arreglo indexado para chunking
        $records = array_values($batch);

        // Procesar en lotes de 100
        $chunks = array_chunk($records, 100);

        $insertados = 0;

        foreach ($chunks as $chunk) {
            // Tomar los códigos del chunk
            $codigos = array_column($chunk, 'res_rfc');

            // Consultar cuáles ya existen en una sola query
            $existentes = DB::table('resguardantes')
                ->whereIn('res_rfc', $codigos)
                ->pluck('res_rfc')
                ->all();

            // Filtrar los que no existen aún
            $existentesMap = array_flip($existentes);
            $toInsert = array_values(array_filter($chunk, function ($item) use ($existentesMap) {
                return !isset($existentesMap[$item['res_rfc']]);
            }));

            if (!empty($toInsert)) {
                DB::table('resguardantes')->insert($toInsert);
                $insertados += count($toInsert);
            }
        }

        $this->command?->info("Seeding finalizado. Insertados: {$insertados} nuevos registros.");
    }
}