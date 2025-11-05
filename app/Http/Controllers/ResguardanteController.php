<?php

namespace App\Http\Controllers;

use App\Models\Resguardante;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException; // Para la excepción
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\Rule;
use Throwable;
class ResguardanteController extends Controller
{
    /**
     * Muestra la lista de resguardantes.
     * (Corregido para 'res_apellidos' y 'res_rfc')
     */
    public function index(Request $request)
    {
        $query = Resguardante::with('departamento.area')
        ->join('usuarios', 'resguardantes.res_id_usuario', '=', 'usuarios.id')
        ->select('resguardantes.*', 'usuarios.usuario_id_rol');

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('res_nombre', 'like', "%{$searchTerm}%")
                  ->orWhere('res_apellidos', 'like', "%{$searchTerm}%") // <-- CORREGIDO
                  ->orWhere('res_correo', 'like', "%{$searchTerm}%")
                  ->orWhere('res_rfc', 'like', "%{$searchTerm}%");      // <-- AÑADIDO
            });
        }

        $resguardantes = $query->latest()->paginate(10);

        return $resguardantes;
    }

    /**
     * Almacena un nuevo resguardante.
     * (Corregido para 'res_apellidos' y 'res_rfc')
     */

        public function store(Request $request)
    {
        // 1. Validar TODOS los datos (del Resguardante y del Usuario)
        // Usamos las columnas corregidas 'res_apellidos' y 'res_rfc'
        $validatedData = $request->validate([
            // Datos del Resguardante
            'res_nombre' => 'required|string|max:255',
            'res_apellidos' => 'required|string|max:255',
            'res_puesto' => 'required|string|max:255',
            'res_rfc' => 'nullable|string|size:13|unique:resguardantes,res_rfc',
            'id_oficina' => 'nullable|integer|exists:oficinas,id',
            'res_telefono' => 'nullable|string|max:20',
            'res_departamento' => 'required|integer|exists:departamentos,id',
            
            // Datos del Usuario
            // El correo debe ser único en AMBAS tablas
            'res_correo' => 'required|email|max:255|unique:resguardantes,res_correo|unique:usuarios,usuario_correo',
            'usuario_pass' => 'required|string|min:8', // Asume que el front envía 'password'
            'usuario_id_rol' => 'required|integer|exists:roles,id|gte:3', // Valida que el rol sea > 3
        ]);

        $resguardante = null;

        try {
            // 2. Iniciar la transacción
            DB::beginTransaction();

            // 3. Crear el Usuario primero
            $usuario = Usuario::create([
                'usuario_nombre' => $validatedData['res_nombre'] . ' ' . $validatedData['res_apellidos'],
                'usuario_correo' => $validatedData['res_correo'],
                'usuario_pass' => Hash::make($validatedData['usuario_pass']), // ¡Hashear la contraseña!
                'usuario_id_rol' => $validatedData['usuario_id_rol'],
            ]);

            // 4. Crear el Resguardante, vinculando el ID del nuevo usuario
            $resguardante = Resguardante::create([
                'res_nombre' => $validatedData['res_nombre'],
                'res_apellidos' => $validatedData['res_apellidos'],
                'res_puesto' => $validatedData['res_puesto'],
                'res_rfc' => $validatedData['res_rfc'] ?? null,
                'res_correo' => $validatedData['res_correo'],
                'res_telefono' => $validatedData['res_telefono'] ?? null,
                'res_departamento' => $validatedData['res_departamento'],
                'id_oficina' => $validatedData['id_oficina'] ?? null,
                'res_id_usuario' => $usuario->id, // <-- ¡LA VINCULACIÓN!
            ]);

            // 5. Si todo salió bien, confirmar los cambios
            DB::commit();

        } catch (Throwable $e) {
            // 6. Si algo falla (ej. error de BD), deshacer todo
            DB::rollBack();
            
            // --- ¡AQUÍ ESTÁ LA CORRECCIÓN! ---
            // Ahora capturamos la excepción específica de "Violación Única"
            if ($e instanceof UniqueConstraintViolationException) {
                
                // Revisa si el error es sobre el nombre de usuario
                if (str_contains($e->getMessage(), 'usuarios_usuario_nombre_unique')) {
                    return response()->json([
                        'message' => 'El nombre de usuario ya existe.',
                        'errors' => [
                            'res_nombre' => ['Ya existe un usuario con este nombre y apellidos.']
                        ]
                    ], 422);
                }
                
                // Revisa si es sobre el correo (aunque la validación de Laravel debería atrapar esto primero)
                if (str_contains($e->getMessage(), 'usuarios_usuario_correo_unique') || str_contains($e->getMessage(), 'resguardantes_res_correo_unique')) {
                    return response()->json([
                        'message' => 'El correo ya existe.',
                        'errors' => [
                            'res_correo' => ['Este correo electrónico ya está en uso.']
                        ]
                    ], 422);
                }
            }
            
            // Si es cualquier otro error, devuelve un 500
            return response()->json([
                'error' => 'Error al crear el resguardante y el usuario. La operación fue revertida.',
                'message' => $e->getMessage()
            ], 500);

        // 7. Devolver el resguardante creado (con su departamento)
        return response()->json($resguardante->load('departamento.area', 'usuario','oficina.edificio'), 201);
    }
}
    

    /**
     * Muestra un resguardante específico.
     * (Sin cambios)
     */
    public function show(Resguardante $resguardante)
    {
        return $resguardante->load('departamento.area', 'usuario','oficina.edificio');
    }

/**
     * Actualiza un resguardante y su usuario correspondiente.
     * (¡CORREGIDO con Transacción para 2 tablas!)
     */
    public function update(Request $request, Resguardante $resguardante)
    {
        // 1. Validar los datos de entrada
        $validatedData = $request->validate([
            // --- Datos del Resguardante ---
            'res_nombre' => 'required|string|max:255',
            'res_apellidos' => 'required|string|max:255',
            'res_puesto' => 'required|string|max:255',
            'res_rfc' => 'nullable|string|size:13|unique:resguardantes,res_rfc,' . $resguardante->id,
            'res_telefono' => 'nullable|string|max:20',
            'res_departamento' => 'required|integer|exists:departamentos,id',
            'id_oficina' => 'nullable|integer|exists:oficinas,id',
            
            // --- Datos de Usuario (Opcionales en el update) ---
            'usuario_pass' => 'nullable|string|min:8', // La contraseña es opcional
            'usuario_id_rol' => 'nullable|integer|exists:roles,id|gte:3', // El rol es opcional

            // --- Campos Únicos (Reglas complejas) ---
            'res_correo' => [
                'required',
                'email',
                'max:255',
                // Debe ser único en 'resguardantes', ignorando el ID del resguardante actual
                Rule::unique('resguardantes', 'res_correo')->ignore($resguardante->id),
                // Debe ser único en 'usuarios', ignorando el ID del usuario vinculado
                Rule::unique('usuarios', 'usuario_correo')->ignore($resguardante->res_id_usuario, 'id')
            ],
            
            // Valida que el 'res_id_usuario' no esté ya asignado a OTRO resguardante
            'res_id_usuario' => 'nullable|exists:usuarios,id|unique:resguardantes,res_id_usuario,' . $resguardante->id,
        ]);

        try {
            // 2. Iniciar la transacción
            DB::beginTransaction();

            // 3. Actualizar el Resguardante
            // (Filtramos solo los campos que pertenecen al modelo Resguardante)
            $resguardanteData = $request->only([
                'res_nombre', 'res_apellidos', 'res_puesto', 'res_rfc',
                'res_correo', 'res_telefono', 'res_departamento', 'id_oficina'
            ]);
            $resguardante->update($resguardanteData);

            // 4. Buscar y Actualizar el Usuario
            $usuario = Usuario::find($resguardante->res_id_usuario);
            
            if ($usuario) {
                // Prepara los datos a actualizar del usuario
                $usuarioData = [
                    'usuario_nombre' => $validatedData['res_nombre'] . ' ' . $validatedData['res_apellidos'],
                    'usuario_correo' => $validatedData['res_correo'],
                ];

                // Solo actualiza la contraseña SI SE ENVIÓ una nueva
                if ($request->filled('usuario_pass')) {
                    $usuarioData['usuario_pass'] = Hash::make($validatedData['usuario_pass']);
                }
                
                // Solo actualiza el rol SI SE ENVIÓ uno nuevo
                if ($request->filled('usuario_id_rol')) {
                    $usuarioData['usuario_id_rol'] = $validatedData['usuario_id_rol'];
                }

                $usuario->update($usuarioData);
            }

            // 5. Si todo salió bien, confirmar los cambios
            DB::commit();

        } catch (Throwable $e) {
            // 6. Si algo falla, deshacer todo
            DB::rollBack();
            
            return response()->json([
                'error' => 'Error al actualizar el resguardante y el usuario. La operación fue revertida.',
                'message' => $e->getMessage()
            ], 500);
        }

        // 7. Devolver el resguardante actualizado (cargando sus relaciones)
        return response()->json($resguardante->load('departamento.area', 'usuario.rol', 'oficina.edificio'), 200);
    }
/**
     * Elimina un resguardante y su usuario asociado.
     * (¡CORREGIDO con Transacción!)
     */
    public function destroy(Resguardante $resguardante)
    {
        try {
            // Inicia una transacción
            DB::beginTransaction();

            // 1. Guarda el ID del usuario antes de borrar el resguardante
            $usuarioId = $resguardante->res_id_usuario;

            // 2. Elimina el Resguardante
            // (Si falla por una FK, el catch(QueryException) lo atrapará)
            $resguardante->delete();

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

        } catch (QueryException $e) { // Error de FK (Bienes asignados)
            DB::rollBack();
            return response()->json([
                'error' => 'No se puede eliminar el resguardante porque tiene bienes asignados.'
            ], 409); // 409 Conflict

        } catch (Throwable $e) { // Otro tipo de error
            DB::rollBack();
            return response()->json([
                'error' => 'Ocurrió un error durante la eliminación.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera un reporte.
     * (Sin cambios)
     */
    public function reporte(Request $request)
    {
        return response()->json([
            'info' => 'Función de reporte aún no implementada.'
        ], 501);
    }
}