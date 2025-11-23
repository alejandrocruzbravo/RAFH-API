<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Departamento>
 */
class DepartamentoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
   // database/factories/DepartamentoFactory.php
    public function definition(): array
    {
        return [
            // Definimos valores por defecto (por si creamos deptos aleatorios)
            'dep_nombre' => fake()->jobTitle(),
            'dep_codigo' => 'D' . fake()->unique()->numerify('###'),
            'dep_resposable' => fake()->name(), 
            'dep_correo_institucional' => fake()->companyEmail(),
            // 'id_area' => Area::factory(), // Si lo necesitas
        ];
    }
}
