<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permisos')->insert([
            [
                'permiso_nombre'=>'crear nuevo bien',
                'permiso_desc'=>'Registrar nuevos bienes o productos en el sistema',
            ],
            [
                'permiso_nombre'=>'editar bien',
                'permiso_desc'=>'Modificar la información de un producto existente',
            ],
            [
                'permiso_nombre'=>'elimar bien',
                'permiso_desc'=>'Eliminar un producto del inventario',
            ],
            [
                'permiso_nombre'=>'crear asignacion',
                'permiso_desc'=>'Asignar un bien a un usuario resguardante',
            ],
            [
                'permiso_nombre'=>'eliminar asignacion',
                'permiso_desc'=>'Quitar la asignación de un bien y devolverlo al stock',
            ],
            [
                'permiso_nombre'=>'ver-asignaciones propias',
                'permiso_desc'=>'Permite a un resguardante ver únicamente los bienes que
                tiene asignados',
            ],
            [
                'permiso_nombre'=>'ver-asignacion departamento',
                'permiso_desc'=>'Ver todos los bienes asignados a los usuarios de
                un área o departamento específico',
            ],
            [
                'permiso_nombre'=>'ver-todas asignaciones',
                'permiso_desc'=>'Acceso total para ver todas las asignaciones en el centro de
                trabajo.',
            ],
            [
                'permiso_nombre'=>'generar resguardo',
                'permiso_desc'=>'Imprimir o generar el documento PDF de un resguardo o
                asignación',
            ],
            [
                'permiso_nombre'=>'ver usuarios',
                'permiso_desc'=>'Ver la lista de todos los usuarios del centro de trabajo.',
            ],
            [
                'permiso_nombre'=>'crear usuarios',
                'permiso_desc'=>'Dar de alta nuevos usuarios',
            ],
            [
                'permiso_nombre'=>'editar usuarios',
                'permiso_desc'=>'Modificar la información de un usuario',
            ],
            [
                'permiso_nombre'=>'eliminar usuarios',
                'permiso_desc'=>'Desactivar o eliminar la cuenta de un usuario.',
            ],
            [
                'permiso_nombre'=>'asignar roles',
                'permiso_desc'=>'Asignar o cambiar el rol de un usuario',
            ],
            [
                'permiso_nombre'=>'ver roles',
                'permiso_desc'=>'Ver la lista de roles existentes.',
            ],
            [
                'permiso_nombre'=>'crear roles',
                'permiso_desc'=>'Crear nuevos roles en el sistema ',
            ],
            [
                'permiso_nombre'=>'editar roles',
                'permiso_desc'=>'Cambiar el nombre o la descripción de un rol',
            ],
            [
                'permiso_nombre'=>'eliminar roles',
                'permiso_desc'=>'Eliminar un rol',
            ],
            [
                'permiso_nombre'=>'asignar permisos a rol',
                'permiso_desc'=>'Permite editar qué puede hacer cada
                rol.',
            ],
        ]);
    }
}
