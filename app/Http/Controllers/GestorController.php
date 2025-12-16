<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gestor;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Throwable;

/**
 * @OA\Tag(
 * name="Gestores",
 * description="Endpoints para la gestión de Gestores (Usuarios con rol de Gestor)"
 * )
 */
class GestorController extends Controller
{
    /**
     * Listar Gestores
     *
     * Muestra una lista paginada de todos los gestores.
     *
     * @OA\Get(
     * path="/gestores",
     * tags={"Gestores"},
     * summary="Listar todos los gestores",
     * @OA\Parameter(name="page", in="query", description="Número de página", required=false, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Lista paginada de gestores",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="data", type="array", @OA\Items(type="object")),
     * @OA\Property(property="current_page", type="integer"),
     * @OA\Property(property="total", type="integer")
     * )
     * )
     * )
     */
    public function index(Request $request)
    {
        $query = Gestor::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            
            $query->where(function($q) use ($search) {
                $q->where('gestor_nombre', 'LIKE', "%{$search}%")
                ->orWhere('gestor_apellidos', 'LIKE', "%{$search}%")
                ->orWhere('gestor_correo', 'LIKE', "%{$search}%")
                ->orWhereRaw("CONCAT(gestor_nombre, ' ', gestor_apellidos) LIKE ?", ["%{$search}%"]);

            });
        }
        $query->orderBy('id', 'desc');
        return response()->json($query->paginate(15));
    }

    /**
     * Crear Gestor
     *
     * Crea un nuevo gestor y automáticamente le crea una cuenta de usuario con Rol de Gestor (ID 2).
     *
     * @OA\Post(
     * path="/gestores",
     * tags={"Gestores"},
     * summary="Registrar un nuevo gestor y su usuario",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"gestor_nombre", "gestor_apellidos", "gestor_correo", "usuario_pass"},
     * @OA\Property(property="gestor_nombre", type="string", example="Juan"),
     * @OA\Property(property="gestor_apellidos", type="string", example="Pérez"),
     * @OA\Property(property="gestor_correo", type="string", format="email", example="juan.gestor@example.com"),
     * @OA\Property(property="usuario_pass", type="string", format="password", example="password123", description="Contraseña para el acceso al sistema")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Gestor y usuario creados exitosamente",
     * @OA\JsonContent(type="object")
     * ),
     * @OA\Response(response=422, description="Error de validación (correo duplicado)"),
     * @OA\Response(response=500, description="Error en la transacción")
     * )
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
     * Ver Gestor
     *
     * Muestra los detalles de un gestor específico y su usuario asociado.
     *
     * @OA\Get(
     * path="/gestores/{id}",
     * tags={"Gestores"},
     * summary="Obtener detalles de un gestor",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Datos del gestor",
     * @OA\JsonContent(type="object")
     * ),
     * @OA\Response(response=404, description="Gestor no encontrado")
     * )
     */
    public function show(Gestor $gestore) // Usando la variable corregida
    {
        return $gestore->load('usuario'); 
    }

    /**
     * Actualizar Gestor
     *
     * Actualiza la información del gestor y sincroniza los cambios en su usuario asociado.
     *
     * @OA\Put(
     * path="/gestores/{id}",
     * tags={"Gestores"},
     * summary="Actualizar gestor",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"gestor_nombre", "gestor_apellidos", "gestor_correo"},
     * @OA\Property(property="gestor_nombre", type="string"),
     * @OA\Property(property="gestor_apellidos", type="string"),
     * @OA\Property(property="gestor_correo", type="string", format="email"),
     * @OA\Property(property="usuario_pass", type="string", format="password", description="Opcional: Nueva contraseña"),
     * @OA\Property(property="gestor_id_usuario", type="integer", nullable=true, description="ID del usuario vinculado (para validación)")
     * )
     * ),
     * @OA\Response(response=200, description="Gestor actualizado"),
     * @OA\Response(response=422, description="Error de validación"),
     * @OA\Response(response=500, description="Error en la transacción")
     * )
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
     * Eliminar Gestor
     *
     * Elimina al gestor y también elimina su cuenta de usuario asociada.
     *
     * @OA\Delete(
     * path="/gestores/{id}",
     * tags={"Gestores"},
     * summary="Eliminar gestor y su usuario",
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=204, description="Eliminado exitosamente"),
     * @OA\Response(response=409, description="Conflicto: Tiene registros asociados"),
     * @OA\Response(response=500, description="Error en la transacción")
     * )
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