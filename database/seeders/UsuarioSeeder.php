<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('usuarios')->insert([
            [
                'usuario_nombre' => 'Root',
                'usuario_correo' => 'root@rafh.com',
                'usuario_pass' => Hash::make('root'),
                'usuario_id_rol' => 1,
            ],
            [
                'usuario_nombre' => 'Resguardante1',
                'usuario_correo' => 'res1@rafh.com',
                'usuario_pass' => Hash::make('res1'),
                'usuario_id_rol' => 3, 
            ]
        ],
        
    );
    }
}
?>
