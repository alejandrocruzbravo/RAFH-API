<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ResguardosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $path = database_path('seeders/data/datosResguardosBienes.csv');
        $duplicateRecords = [];
        $file = new \SplFileObject($path);
        $file->setFlags(\SplFileObject::READ_CSV);
        $file->setCsvControl(',');
        $header = true;

        $registros = [];

        foreach ($file as $row) {
            if ($header) { $header = false; continue; }
            if (!$row || count($row) < 4) { 
                continue;
            }

            [$fecalt, $usuario1, $clv_nue, $sec_nue] = $row;

            if (!$usuario1 || !$clv_nue || !$sec_nue) { continue; }

            // Normaliza fecha
            $fecaltPg = !empty($fecalt) ? Carbon::createFromFormat('d-M-Y',$fecalt) : null;

            // Verifica existencia en otra_tabla
            $bienId = DB::table('bienes')
                ->where('bien_codigo', \App\Models\Bien::generarCodigo($sec_nue,$clv_nue,$fecaltPg))
                ->value('id');
            $reguardanteId = DB::table('resguardantes')
                ->where('res_rfc',$usuario1)
                ->value('id');

            if (!$bienId || !$reguardanteId) {
                continue;
            }else{
                $registros[] = [
                    'resguardo_id_bien'   => $bienId,
                    'resguardo_id_resguardante' => $reguardanteId,
                    'resguardo_fecha_asignacion'=>Carbon::now(),
                    'resguardo_id_dep'  => 5,
                ];
            }
            
        }

        if (!empty($registros)) {
        $registros = collect($registros)
            ->unique(fn($item) => $item['resguardo_id_bien'].'-'.$item['resguardo_id_resguardante'])
            ->values()
            ->toArray();

            
            foreach (array_chunk($registros, 1000) as $chunk) {
                            DB::table('resguardos')->upsert(
                                $chunk,
                                ['resguardo_id_bien', 'resguardo_id_resguardante'],
                                ['resguardo_fecha_asignacion', 'resguardo_id_dep']
                            );
            }

        }


        
    }
}
