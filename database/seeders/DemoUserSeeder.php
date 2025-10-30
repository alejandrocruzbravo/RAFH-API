<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Usuario; // Basado en tu diagrama
use App\Models\Rol;     // Basado en tu diagrama
use App\Models\Resguardante;
use App\Models\Departamento;

class DemoUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // --- 1. CREAR LOS ROLES ---
        // Usamos firstOrCreate para no crear duplicados si el seeder se corre varias veces
        $rolJefe = Rol::firstOrCreate(
            ['rol_nombre' => 'Jefe de Departamento']
        );
        
        $rolSubdirector = Rol::firstOrCreate(
            ['rol_nombre' => 'Subdirector']
        );

        // --- 2. OBTENER UN DEPARTAMENTO (DEPENDENCIA) ---
        // !! Asumimos que el seeder de Departamentos YA SE EJECUTÓ !!
        $departamento = Departamento::first(); // Tomamos el primer depto que exista

        // Si no hay departamentos, no podemos crear al resguardante.
        if (!$departamento) {
            $this->command->error('No se encontraron departamentos. El usuario "Jefe de Departamento" no pudo ser creado como Resguardante.');
            // Podemos continuar para crear al Subdirector, que no tiene esta dependencia.
        }

        // --- 3. CREAR USUARIO "SUBDIRECTOR" ---
        // Este usuario es simple, no es Resguardante.
        Usuario::firstOrCreate(
            ['usuario_correo' => 'subdirector@example.com'], // La clave para buscar
            [ // Los datos para crear si no existe
                'usuario_nombre' => 'Carlos Subdirector',
                'usuario_pass' => Hash::make('password'), // ¡Cambia esto por una contraseña segura!
                'usuario_id_rol' => $rolSubdirector->id
            ]
        );

        // --- 4. CREAR USUARIO "JEFE DE DEPARTAMENTO" (CON RESGUARDANTE) ---
        // Solo si encontramos un departamento al cual asignarlo
        if ($departamento) {
            
            // 4a. Creamos el registro de Usuario
            $usuarioJefe = Usuario::firstOrCreate(
                ['usuario_correo' => 'jefe.depto@example.com'],
                [
                    'usuario_nombre' => 'Ana Jefa',
                    'usuario_pass' => Hash::make('password'), // ¡Cambia esto!
                    'usuario_id_rol' => $rolJefe->id
                ]
            );

            // 4b. Creamos su perfil de Resguardante (vinculado al Usuario)
            Resguardante::firstOrCreate(
                ['res_id_usuario' => $usuarioJefe->id], // Clave para buscar
                [ // Datos para crear si no existe
                    'res_nombre' => $usuarioJefe->usuario_nombre,
                    'res_apellido1' => 'García', // Dato de ejemplo
                    'res_apellido2' => 'Pérez',  // Dato de ejemplo
                    'res_puesto' => 'Jefe de Departamento',
                    'res_correo' => $usuarioJefe->usuario_correo,
                    'res_telefono' => '9830000001', // Dato de ejemplo
                    'res_departamento' => $departamento->id, // ¡La dependencia clave!
                ]
            );
        }
    }
}