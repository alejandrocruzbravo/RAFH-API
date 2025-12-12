<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario;
use App\Models\Rol;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Usuario>
 */
class UsuarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
                'usuario_nombre' => $this->faker->name(),
                'usuario_correo' => $this->faker->unique()->safeEmail(),
                'usuario_pass' => Hash::make('password'), 
                'usuario_id_rol' => Rol::factory(),

            ];

    }
    public function administrador()
    {
        return $this->state(function (array $attributes) {
            return [
                'usuario_id_rol' => Rol::factory()->state([
                    'rol_nombre' => 'Administrador', 
                ]),
            ];
        });
    }
}
