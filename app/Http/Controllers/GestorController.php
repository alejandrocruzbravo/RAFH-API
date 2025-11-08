<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gestor;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
        // 1. Validar los datos (¡ELIMINAMOS 'usuario_id_rol' de aquí!)
        $validatedData = $request->validate([
            'gestor_nombre' => 'required|string|max:255',
            'gestor_apellidos' => 'required|string|max:255',
            
            // El correo debe ser único en AMBAS tablas
            'gestor_correo' => 'required|email|max:255|unique:gestores,gestor_correo|unique:usuarios,usuario_correo',
            'usuario_pass' => 'required|string|min:8',
        ]);

        $gestor = null; 

        try {
            DB::beginTransaction();

            // 3. Crear el Usuario primero
            $usuario = Usuario::create([
                'usuario_nombre' => $validatedData['gestor_nombre'] . ' ' . $validatedData['gestor_apellidos'],
                'usuario_correo' => $validatedData['gestor_correo'],
                'usuario_pass' => Hash::make($validatedData['usuario_pass']), 
                
                // --- ¡AQUÍ ESTÁ LA CORRECCIÓN! ---
                // Asignamos el Rol ID 2 (Gestor) automáticamente.
                // Ignoramos cualquier 'usuario_id_rol' que el frontend intente enviar.
                'usuario_id_rol' => 2, 
            ]);

            // 4. Crear el Gestor, vinculando el ID del nuevo usuario
            $gestor = Gestor::create([
                'gestor_nombre' => $validatedData['gestor_nombre'],
                'gestor_apellidos' => $validatedData['gestor_apellidos'],
                'gestor_correo' => $validatedData['gestor_correo'],
                'gestor_id_usuario' => $usuario->id, // <-- ¡LA VINCULACIÓN!
            ]);

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al crear el gestor y el usuario.',
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
/**
     * Actualiza un gestor y su usuario correspondiente.
     */
    public function update(Request $request, Gestor $gestore)
    {
        // 1. Validar los datos (¡ELIMINAMOS 'usuario_id_rol' de aquí!)
        $validatedData = $request->validate([
            'gestor_nombre' => 'required|string|max:255',
            'gestor_apellidos' => 'required|string|max:255',
            'usuario_pass' => 'nullable|string|min:8', // Opcional: permitir cambiar contraseña
            
            'gestor_correo' => [
                'required', 'email', 'max:255',
                Rule::unique('gestores', 'gestor_correo')->ignore($gestore->id),
                Rule::unique('usuarios', 'usuario_correo')->ignore($gestore->gestor_id_usuario, 'id')
            ],
            
            'gestor_id_usuario' => 'nullable|integer|exists:usuarios,id|unique:gestores,gestor_id_usuario,' . $gestore->id,
        ]);

        try {
            DB::beginTransaction();

            // 3. Actualizar el Gestor
            $gestorData = $request->only(['gestor_nombre', 'gestor_apellidos', 'gestor_correo']);
            $gestore->update($gestorData);

            // 4. Buscar y Actualizar el Usuario
            $usuario = Usuario::find($gestore->gestor_id_usuario);
            
            if ($usuario) {
                $usuarioData = [
                    'usuario_nombre' => $validatedData['gestor_nombre'] . ' ' . $validatedData['gestor_apellidos'],
                    'usuario_correo' => $validatedData['gestor_correo'],
                ];
                
                if ($request->filled('usuario_pass')) {
                    $usuarioData['usuario_pass'] = Hash::make($validatedData['usuario_pass']);
                }
                
                // ¡IMPORTANTE! Ya no actualizamos el 'usuario_id_rol'.
                // Un gestor siempre es un gestor (Rol ID 2).

                $usuario->update($usuarioData);
            }

            DB::commit();

        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al actualizar el gestor y el usuario.',
                'message' => $e->getMessage()
            ], 500);
        }

        return response()->json($gestore->load('usuario'), 200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gestor $gestore)
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
