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
use App\Models\Rol;
use Throwable;
class ResguardanteController extends Controller
{
    /**
     * Muestra la lista de resguardantes.
     * (Corregido para 'res_apellidos' y 'res_rfc')
     */
    public function index(Request $request)
    {
        // Inicia la consulta
        $query = Resguardante::with('departamento.area', 'oficina.edificio')
            
            // --- ¡CORRECCIÓN! ---
            // Usa LEFT JOIN para incluir resguardantes sin usuario
            ->leftJoin('usuarios', 'resguardantes.res_id_usuario', '=', 'usuarios.id')
            
            // Selecciona todas las columnas de 'resguardantes' y el 'rol' del usuario (si existe)
            ->select('resguardantes.*', 'usuarios.usuario_id_rol');

        // Lógica de búsqueda
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('resguardantes.res_nombre', 'like', "%{$searchTerm}%")
                  ->orWhere('resguardantes.res_apellidos', 'like', "%{$searchTerm}%")
                  ->orWhere('resguardantes.res_correo', 'like', "%{$searchTerm}%")
                  ->orWhere('resguardantes.res_rfc', 'like', "%{$searchTerm}%");
            });
        }

        // Ordena por el ID del resguardante (usando 'latest' en la tabla principal)
        $resguardantes = $query->latest('resguardantes.created_at')->paginate(10);

        return $resguardantes;
    }
    /**
     * Almacena un nuevo resguardante.
     * (Corregido para 'res_apellidos' y 'res_rfc')
     */

     public function store(Request $request)
     {
         // 1. Validación (ahora incluye res_curp)
         $validatedData = $request->validate([
             'res_nombre' => 'required|string|max:255',
             'res_apellidos' => 'required|string|max:255',
             'res_puesto' => 'required|string|max:255',
             'res_departamento' => 'required|integer|exists:departamentos,id',
             'res_rfc' => 'nullable|string|size:13|unique:resguardantes,res_rfc',
             'res_curp' => 'nullable|string|size:18|unique:resguardantes,res_curp', 
             'res_telefono' => 'nullable|string|max:20',
             'id_oficina' => 'nullable|integer|exists:oficinas,id',
             'res_correo' => 'nullable|email|max:255|unique:resguardantes,res_correo',
         ]);
 
         // 2. Crear el Resguardante
         $resguardante = Resguardante::create($validatedData);
 
         // 3. Devolver el resguardante creado
         return response()->json($resguardante->load('departamento.area', 'oficina.edificio'), 201);
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
        $validatedData = $request->validate([
            'res_nombre' => 'required|string|max:255',
            'res_apellidos' => 'required|string|max:255',
            'res_puesto' => 'required|string|max:255',
            'res_rfc' => 'nullable|string|size:13|unique:resguardantes,res_rfc,' . $resguardante->id,
            'res_curp' => 'nullable|string|size:18|unique:resguardantes,res_curp,' . $resguardante->id,
            'res_telefono' => 'nullable|string|max:20',
            'res_departamento' => 'required|exists:departamentos,id',
            'id_oficina' => 'nullable|integer|exists:oficinas,id',
            'res_correo' => [
                'nullable', 'email', 'max:255',
                Rule::unique('resguardantes')->ignore($resguardante->id),
                Rule::unique('usuarios', 'usuario_correo')->ignore($resguardante->res_id_usuario, 'id')
            ],
            'usuario_id_rol' => 'nullable|integer|exists:roles,id|gte:3'
        ]);

        // (La lógica para actualizar el correo/nombre del usuario si existe es la misma)
        if ($resguardante->res_correo !== $validatedData['res_correo'] && $resguardante->res_id_usuario) {
            try {
                DB::beginTransaction();
                // 2. Actualizar el perfil del Resguardante
                // (El $validatedData extra de 'usuario_id_rol' es ignorado por $fillable, lo cual está bien)
                $resguardante->update($validatedData);
                // 3. Actualizar el Usuario (si existe)
                if ($resguardante->res_id_usuario) {
                    $usuario = Usuario::find($resguardante->res_id_usuario);
                    if ($usuario) {
                        // Prepara los datos a actualizar (sincronizar nombre y correo)
                        $usuarioData = [
                            'usuario_correo' => $validatedData['res_correo'],
                            'usuario_nombre' => $validatedData['res_nombre'] . ' ' . $validatedData['res_apellidos'],
                        ];
                        if ($request->filled('usuario_id_rol')) {
                            $usuarioData['usuario_id_rol'] = $validatedData['usuario_id_rol'];
                        }
    
                        $usuario->update($usuarioData);
                    }
                }
                DB::commit();
    
            } catch (Throwable $e) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Error al actualizar los datos.', 
                    'message' => $e->getMessage()
                ], 500);
            }
    
            return response()->json($resguardante->load('departamento.area', 'usuario.rol', 'oficina.edificio'), 200);
        }

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
     * Crea una cuenta de usuario para un resguardante existente.
     * (Corregido para ASIGNAR un nuevo correo)
     */
    public function crearUsuario(Request $request, Resguardante $resguardante)
    {
        // 1. Validar que el resguardante no tenga ya un usuario
        if ($resguardante->res_id_usuario) {
            return response()->json(['error' => 'Este resguardante ya tiene una cuenta de usuario.'], 409);
        }

        // 2. Validar los datos de entrada (¡NUEVA LÓGICA DE CORREO!)
        $validatedData = $request->validate([
            'usuario_pass' => 'required|string|min:8',
            'usuario_id_rol' => 'nullable|integer|exists:roles,id|gte:3', 
            
            // Este correo será el nuevo correo para el usuario Y el resguardante
            'usuario_correo' => [
                'required', 'email', 'max:255',
                'unique:usuarios,usuario_correo',  // Único en la tabla usuarios
                'unique:resguardantes,res_correo'  // Único en la tabla resguardantes
            ],
        ]);
        // 3. Lógica de Rol por Defecto (sigue igual)
        $roleId = $validatedData['usuario_id_rol'] ?? null;
        if (is_null($roleId)) {
            $rolResguardante = Rol::where('rol_nombre', 'Resguardante')->first();
            if (!$rolResguardante) {
                return response()->json(['error' => 'El rol por defecto "Resguardante" no se encontró.'], 500);
            }
            $roleId = $rolResguardante->id;
        }
        try {
            DB::beginTransaction();
            // 4. Crear el Usuario
            $usuario = Usuario::create([
                'usuario_nombre' => $resguardante->res_nombre . ' ' . $resguardante->res_apellidos,
                'usuario_correo' => $validatedData['usuario_correo'], // <-- Usa el nuevo correo
                'usuario_pass' => Hash::make($validatedData['usuario_pass']),
                'usuario_id_rol' => $roleId,
            ]);
            // 5. Vincular el Usuario Y ACTUALIZAR el correo del Resguardante
            $resguardante->res_id_usuario = $usuario->id;
            $resguardante->res_correo = $validatedData['usuario_correo']; // <-- Asigna el correo
            $resguardante->save();
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al crear el usuario. La operación fue revertida.',
                'message' => $e->getMessage()
            ], 500);
        }
        return response()->json($resguardante->load('usuario.rol'), 201);
    }
}