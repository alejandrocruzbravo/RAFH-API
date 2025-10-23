<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('usuarios')->insert([[
            'usuario_nombre' => 'Root',
            'usuario_correo' => 'root@rafh.com',
            'usuario_pass' => Hash::make('root'),
            'usuario_id_rol' => 1, 
        ],
        [
            'usuario_nombre' => 'Gestor1',
            'usuario_correo' => 'gestor@rafh.com',
            'usuario_pass' => Hash::make('gestor'),
            'usuario_id_rol' => 2, 
        ]]
    );


    }
}
?>
