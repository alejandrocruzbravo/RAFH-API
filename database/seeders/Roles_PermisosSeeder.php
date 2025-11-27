<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Roles_PermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $registros = [];
        for ($i=1; $i < 20; $i++) { 
            # code...
            $registros[] = [
                'id_rol'=>1,
                'id_permiso'=>$i
            ];

        }

        for ($i=1; $i < 20; $i++) { 
            # code...
            if($i == 3)continue;
            $registros[] = [
                'id_rol'=>2,
                'id_permiso'=>$i
            ];

        }
        
        $registrosDemas = [[
            'id_rol'=>3,
            'id_permiso'=>6
        ],
        [
            'id_rol'=>3,
            'id_permiso'=>9
        ],
        [
            'id_rol'=>4,
            'id_permiso'=>4
        ],
        [
            'id_rol'=>4,
            'id_permiso'=>6
        ],
        [
            'id_rol'=>4,
            'id_permiso'=>7
        ],
        [
            'id_rol'=>4,
            'id_permiso'=>9
        ],
        [
            'id_rol'=>4,
            'id_permiso'=>10
        ]
        ];

        foreach ($registrosDemas as $records) {
            # code...
            $registros[] = $records;
        }




        DB::table('roles_permisos')->insert($registros);
    }
}
