<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartamentosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        DB::table('departamentos')->insert([
            ['dep_nombre' => 'Recursos Humanos', 'dep_codigo'=> 'D'. fake()->unique()->numerify('###').''],
            ['dep_nombre' => 'Finanzas','dep_codigo'=> 'D'. fake()->unique()->numerify('###').''],
            ['dep_nombre' => 'Tecnología','dep_codigo'=> 'D'. fake()->unique()->numerify('###').''],
            ['dep_nombre' => 'Marketing','dep_codigo'=> 'D'. fake()->unique()->numerify('###').''],
        ]);


    }
}
