<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gestor;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;     // <-- AÑADE ESTA
use Illuminate\Support\Facades\Hash;  // <-- AÑADE ESTA
use Throwable;

class GestorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $gestores = Gestor::latest()->paginate(10); 
        return $gestores;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Validar TODOS los datos (del Gestor y del Usuario)
        $validatedData = $request->validate([
            // Datos del Gestor
            'gestor_nombre' => 'required|string|max:255',
            'gestor_apellidos' => 'required|string|max:255',
            
            // Datos del Usuario (nuevos)
            // El correo debe ser único en AMBAS tablas
            'gestor_correo' => 'required|email|max:255|unique:gestores,gestor_correo|unique:usuarios,usuario_correo',
            'usuario_pass' => 'required|string|min:8', // Considera añadir 'confirmed' si envías 'usuario_pass_confirmation'
            'usuario_id_rol' => 'required|integer|exists:roles,id',
        ]);

        $gestor = null; // Inicializamos la variable

        try {
            // 2. Iniciar la transacción
            DB::beginTransaction();

            // 3. Crear el Usuario primero
            $usuario = Usuario::create([
                // Asumimos que el nombre de usuario es el nombre del gestor
                'usuario_nombre' => $validatedData['gestor_nombre'] . ' ' . $validatedData['gestor_apellidos'],
                'usuario_correo' => $validatedData['gestor_correo'],
                'usuario_pass' => Hash::make($validatedData['usuario_pass']), // ¡Hashear la contraseña!
                'usuario_id_rol' => $validatedData['usuario_id_rol'],
            ]);

            // 4. Crear el Gestor, vinculando el ID del nuevo usuario
            $gestor = Gestor::create([
                'gestor_nombre' => $validatedData['gestor_nombre'],
                'gestor_apellidos' => $validatedData['gestor_apellidos'],
                'gestor_correo' => $validatedData['gestor_correo'],
                'gestor_id_usuario' => $usuario->id, // <-- ¡AQUÍ ESTÁ LA VINCULACIÓN!
            ]);

            // 5. Si todo salió bien, confirmar los cambios
            DB::commit();

        } catch (Throwable $e) {
            // 6. Si algo falla (ej. error de BD), deshacer todo
            DB::rollBack();
            
            // Devolver un error 500
            return response()->json([
                'error' => 'Error al crear el gestor y el usuario. La operación fue revertida.',
                'message' => $e->getMessage()
            ], 500);
        }

        return response()->json($gestor, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Gestor $gestore) // Usando la variable corregida
    {
        return $gestore->load('usuario'); 
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Gestor $gestore)
    {
        // 1. Validar los datos de entrada
        $validatedData = $request->validate([
            'gestor_nombre' => 'required|string|max:255',
            'gestor_apellidos' => 'required|string|max:255',
            'gestor_correo' => 'required|email|max:255|unique:gestores,gestor_correo,' . $gestore->id,
            'gestor_id_usuario' => 'nullable|integer|exists:usuarios,id|unique:gestores,gestor_id_usuario,' . $gestore->id,
        ]);

        // 2. Actualizar el gestor
        $gestore->update($validatedData);
        return response()->json($gestore, 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gestor $gestore) // Usando la variable corregida
    {
        try {
            // Inicia una transacción
            DB::beginTransaction();

            // 1. Guarda el ID del usuario antes de borrar el gestor
            $usuarioId = $gestore->gestor_id_usuario;

            // 2. Elimina el Gestor
            $gestore->delete();

            // 3. Busca y elimina el Usuario asociado (si existe)
            if ($usuarioId) {
                $usuario = Usuario::find($usuarioId);
                if ($usuario) {
                    $usuario->delete();
                }
            }
            
            // 4. Confirma la transacción
            DB::commit();
            
            // Retorna una respuesta exitosa sin contenido
            return response()->json(null, 204);

        } catch (Throwable $e) { // Captura cualquier error
            // 5. Si algo falla, revierte todos los cambios
            DB::rollBack();

            // Manejar error de llave foránea (si el gestor aún tiene registros)
            if ($e instanceof QueryException) {
                return response()->json([
                    'error' => 'No se puede eliminar el gestor porque tiene otros registros asociados.'
                ], 409); // 409 Conflict
            }

            // Otro tipo de error
            return response()->json([
                'error' => 'Ocurrió un error durante la eliminación.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
