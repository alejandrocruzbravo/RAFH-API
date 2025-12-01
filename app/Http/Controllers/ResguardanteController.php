<?php

namespace App\Http\Controllers;

use App\Models\Resguardante;
use App\Models\Usuario;
use App\Models\Oficina;
use App\Models\Bien;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException; // Para la excepción
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\Rule;
use App\Models\Rol;
use Throwable;
/**
 * @OA\Tag(
 * name="Resguardantes",
 * description="Endpoints para la gestión de los resguardantes"
 * )
 */
class ResguardanteController extends Controller
{
    /**
     * Listar Resguardantes
     *
     * Obtiene una lista paginada de todos los resguardantes registrados.
     * Se puede filtrar por nombre, apellido o correo.
     *
     * @OA\Get(
     * path="/resguardantes",
     * tags={"Resguardantes"},
     * summary="Listar todos los resguardantes",
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Término para buscar por nombre, apellido o correo",
     * required=false,
     * @OA\Schema(type="string")
     * ),
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Número de página para la paginación",
     * required=false,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Operación exitosa"
     * ),
     * @OA\Response(
     * response=401,
     * description="No autenticado"
     * )
     * )
     */
    public function index(Request $request)
    {
        // Inicia la consulta
        $query = Resguardante::with('departamento.area', 'oficina.edificio')
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
        $resguardantes = $query->latest('resguardantes')->paginate(10);
        return $resguardantes;
    }
    /**
     * Almacena un nuevo resguardante.
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
     */
    public function show(Resguardante $resguardante)
    {
        return $resguardante->load('departamento.area', 'usuario','oficina.edificio');
    }

/**
     * Actualiza un resguardante y su usuario correspondiente.
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
        }else{
            // Solo actualizar el Resguardante si el correo no cambió o no hay usuario asociado
            $resguardante->update($validatedData);
        }
        return response()->json($resguardante->load('departamento.area', 'usuario.rol', 'oficina.edificio'), 200);
    }
/**
     * Elimina un resguardante y su usuario asociado.
     */
    public function destroy(Resguardante $resguardante)
    {
        // Verificamos explícitamente si tiene bienes asignados.
        if ($resguardante->bienes()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar al resguardante porque tiene bienes bajo su custodia. Libere los bienes primero.'
            ], 409);
        }

        try {
            DB::beginTransaction();

            // 2. Guardar ID de usuario asociado
            $usuarioId = $resguardante->res_id_usuario;

            // 3. Eliminar Resguardante
            $resguardante->delete();

            // 4. Eliminar Usuario de sistema asociado (si existe)
            if ($usuarioId) {
                // Usamos where para evitar error si el usuario ya no existe
                \App\Models\Usuario::where('id', $usuarioId)->delete();
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Resguardante eliminado correctamente.'
            ], 200); // Usamos 200 para poder devolver el mensaje JSON. 204 No Content no devuelve cuerpo.

        } catch (\Throwable $e) { 
            DB::rollBack();
            return response()->json([
                'message' => 'Ocurrió un error interno durante la eliminación.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crea una cuenta de usuario para un resguardante existente.
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
                Rule::unique('resguardantes', 'res_correo')->ignore($resguardante->id),  // Único en la tabla resguardantes excluyendo este resguardante
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

    public function bienesAsignados(Request $request , $id)
    {
        $query = Bien::where('id_resguardante', $id);

        // Filtro de búsqueda (si el usuario escribe en la nueva barra)
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function($q) use ($term) {
                $q->where('bien_codigo', 'like', "%{$term}%")
                  ->orWhere('bien_descripcion', 'like', "%{$term}%")
                  ->orWhere('bien_serie', 'like', "%{$term}%");
            });
        }
        if ($request->has('estado')) {
            $query->where('estado', $request->input('estado'));
        }
        // Paginación de 15 registros como solicitaste
        $bienes = $query->paginate(15);

        return response()->json($bienes);
    }

    public function indexByOficina($oficinaId)
    {
        // Opcional: Verificar que la oficina exista primero
        if (!Oficina::where('id', $oficinaId)->exists()) {
            return response()->json(['message' => 'Oficina no encontrada'], 404);
        }

        // Obtenemos los resguardantes de esa oficina
        $resguardantes = Resguardante::where('id_oficina', $oficinaId)
            ->orderBy('res_apellidos', 'asc') // Orden alfabético es útil
            ->get();

        return response()->json($resguardantes);
    }
    public function misBienes(Request $request)
    {
        $user = $request->user();

        // 1. Verificamos "a la mala" si el usuario tiene un resguardante asociado
        // Usamos la relación que ya tienes en tu modelo Usuario
        if (!$user->resguardante) {
            return response()->json(['message' => 'Tu usuario no tiene perfil de resguardante asignado.'], 403);
        }

        // 2. Obtenemos SU id automáticamente
        $miResguardanteId = $user->resguardante->id;

        // 3. Hacemos la consulta directa (copiada de tu lógica anterior)
        $query = Bien::with('ubicacionActual')
             ->where('id_resguardante', $miResguardanteId);

        // Filtros (Search)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'ILIKE', "%{$search}%")
                ->orWhere('numero_serie', 'ILIKE', "%{$search}%");
            });
        }
        
        // Filtros extra (Estado/Categoria)
        if ($request->has('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        return response()->json($query->paginate(15));
    }
    public function search(Request $request)
    {
        $queryText = $request->input('query');

        if (!$queryText || strlen($queryText) < 3) {
            return response()->json([]);
        }

        // Buscamos resguardantes donde su USUARIO asociado coincida con el nombre o correo
        $resguardantes = Resguardante::whereHas('usuario', function($q) use ($queryText) {
                $q->where('usuario_nombre', 'ILIKE', "%{$queryText}%")
                ->orWhere('usuario_correo', 'ILIKE', "%{$queryText}%");
            })
            ->with('usuario') // Traemos los datos del usuario para mostrar nombre/correo
            ->limit(5)        // Limitamos a 5 para no saturar
            ->get();

        // Mapeamos para enviar solo lo necesario al front
        $data = $resguardantes->map(function($res) {
            return [
                'id' => $res->id, // ID del resguardante (importante para el traspaso)
                'nombre' => $res->usuario->usuario_nombre,
                'correo' => $res->usuario->usuario_correo,
                'cargo'  => $res->res_cargo ?? 'Sin cargo definido', // Ajusta según tu columna real
                'iniciales' => substr($res->usuario->usuario_nombre, 0, 2)
            ];
        });

        return response()->json($data);
    }

}