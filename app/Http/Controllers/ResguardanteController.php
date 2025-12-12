<?php

namespace App\Http\Controllers;

use App\Models\Resguardante;
use App\Models\Usuario;
use App\Models\Oficina;
use App\Models\Bien;
use App\Models\MovimientoBien;
use App\Models\Traspaso;
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
        $query = Resguardante::with('departamento.area', 'oficina.edificio')
            ->leftJoin('usuarios', 'resguardantes.res_id_usuario', '=', 'usuarios.id')
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
        $resguardantes = $query->latest('resguardantes')->paginate(10);
        return $resguardantes;
    }

    /**
     * @OA\Post(
     * path="/resguardantes",
     * summary="Crear un nuevo resguardante",
     * tags={"Resguardantes"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"res_nombre", "res_apellidos", "res_puesto", "res_departamento"},
     * @OA\Property(property="res_nombre", type="string", example="Juan"),
     * @OA\Property(property="res_apellidos", type="string", example="Pérez"),
     * @OA\Property(property="res_puesto", type="string", example="Analista"),
     * @OA\Property(property="res_departamento", type="integer", example=5, description="ID del departamento"),
     * @OA\Property(property="res_rfc", type="string", example="ABCD800101XYZ"),
     * @OA\Property(property="res_curp", type="string", example="ABCD800101HDFRRN01"),
     * @OA\Property(property="res_telefono", type="string", example="5551234567"),
     * @OA\Property(property="id_oficina", type="integer", nullable=true, example=2),
     * @OA\Property(property="res_correo", type="string", format="email", example="juan@example.com")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Resguardante creado",
     * @OA\JsonContent(ref="#/components/schemas/Resguardante")
     * ),
     * @OA\Response(response=422, description="Error de validación")
     * )
     */
    public function store(Request $request)
    {
         // 1. Validación 
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
     * @OA\Get(
     * path="/resguardantes/{id}",
     * summary="Obtener detalles de un resguardante",
     * tags={"Resguardantes"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Detalles del resguardante con relaciones",
     * @OA\JsonContent(ref="#/components/schemas/Resguardante")
     * ),
     * @OA\Response(response=404, description="No encontrado")
     * )
     */
    public function show(Resguardante $resguardante)
    {
        return $resguardante->load('departamento.area', 'usuario','oficina.edificio');
    }

    /**
     * @OA\Put(
     * path="/resguardantes/{id}",
     * summary="Actualizar resguardante",
     * description="Actualiza datos personales y sincroniza correo/nombre con el usuario de sistema asociado si existe.",
     * tags={"Resguardantes"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"res_nombre", "res_apellidos", "res_puesto", "res_departamento"},
     * @OA\Property(property="res_nombre", type="string"),
     * @OA\Property(property="res_apellidos", type="string"),
     * @OA\Property(property="res_puesto", type="string"),
     * @OA\Property(property="res_departamento", type="integer"),
     * @OA\Property(property="id_oficina", type="integer", nullable=true),
     * @OA\Property(property="res_rfc", type="string", nullable=true),
     * @OA\Property(property="res_curp", type="string", nullable=true),
     * @OA\Property(property="res_correo", type="string", format="email"),
     * @OA\Property(property="usuario_id_rol", type="integer", nullable=true, description="ID del rol a actualizar en el usuario asociado")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Resguardante actualizado",
     * @OA\JsonContent(ref="#/components/schemas/Resguardante")
     * ),
     * @OA\Response(response=500, description="Error en transacción")
     * )
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

        if ($resguardante->res_correo !== $validatedData['res_correo'] && $resguardante->res_id_usuario) {
            try {
                DB::beginTransaction();
                // 2. Actualizar el perfil del Resguardante
                $resguardante->update($validatedData);
                // 3. Actualizar el Usuario
                if ($resguardante->res_id_usuario) {
                    $usuario = Usuario::find($resguardante->res_id_usuario);
                    if ($usuario) {
                        // Prepara los datos a actualizar (sincroniza nombre y correo)
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
     * @OA\Delete(
     * path="/resguardantes/{id}",
     * summary="Eliminar resguardante",
     * description="Elimina al resguardante y su usuario asociado. Falla si tiene bienes asignados.",
     * tags={"Resguardantes"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Eliminado correctamente",
     * @OA\JsonContent(@OA\Property(property="message", type="string"))
     * ),
     * @OA\Response(
     * response=409,
     * description="Conflicto: Tiene bienes asignados",
     * @OA\JsonContent(@OA\Property(property="message", type="string"))
     * )
     * )
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
     * @OA\Post(
     * path="/resguardantes/{id}/usuario",
     * summary="Crear usuario de sistema para un resguardante",
     * tags={"Resguardantes"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"usuario_pass", "usuario_correo"},
     * @OA\Property(property="usuario_pass", type="string", format="password", minLength=8),
     * @OA\Property(property="usuario_correo", type="string", format="email"),
     * @OA\Property(property="usuario_id_rol", type="integer", nullable=true)
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Usuario creado y vinculado",
     * @OA\JsonContent(ref="#/components/schemas/Resguardante")
     * ),
     * @OA\Response(response=409, description="El resguardante ya tiene usuario")
     * )
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

    /**
     * @OA\Get(
     * path="/resguardantes/{id}/bienes",
     * summary="Listar bienes asignados a un resguardante (Admin)",
     * tags={"Resguardantes"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     * @OA\Parameter(name="estado", in="query", required=false, @OA\Schema(type="string")),
     * @OA\Response(
     * response=200,
     * description="Lista paginada de bienes",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Bien")),
     * @OA\Property(property="total", type="integer")
     * )
     * )
     * )
     */
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

    /**
     * @OA\Get(
     * path="/oficinas/{id}/resguardantes",
     * summary="Listar resguardantes por oficina",
     * tags={"Resguardantes"},
     * @OA\Parameter(name="id", in="path", description="ID de la oficina", required=true, @OA\Schema(type="integer")),
     * @OA\Response(
     * response=200,
     * description="Lista de resguardantes en la oficina",
     * @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Resguardante"))
     * ),
     * @OA\Response(response=404, description="Oficina no encontrada")
     * )
     */
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

    /**
     * @OA\Get(
     * path="/mis-bienes",
     * summary="Obtener mis bienes (Perfil Resguardante)",
     * description="Lista paginada de los bienes asignados al usuario autenticado.",
     * tags={"Perfil Resguardante"},
     * @OA\Response(
     * response=200,
     * description="Mis bienes",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Bien"))
     * )
     * ),
     * @OA\Response(response=403, description="Usuario sin perfil de resguardante")
     * )
     */
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
        $query = Bien::with(['ubicacionActual', 'traspasoPendiente']) 
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

    /**
     * @OA\Get(
     * path="/resguardantes/search",
     * summary="Búsqueda rápida de resguardantes (Autocomplete)",
     * description="Busca resguardantes por nombre o correo (usuario). Excluye al usuario actual.",
     * tags={"Resguardantes"},
     * @OA\Parameter(name="query", in="query", required=true, @OA\Schema(type="string", minLength=3)),
     * @OA\Response(
     * response=200,
     * description="Resultados simplificados",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="nombre", type="string"),
     * @OA\Property(property="correo", type="string"),
     * @OA\Property(property="cargo", type="string"),
     * @OA\Property(property="tiene_usuario", type="boolean")
     * )
     * )
     * )
     * )
     */
    public function search(Request $request)
    {
        $queryText = $request->input('query');
        $currentUser = $request->user();

        if (!$queryText || strlen($queryText) < 3) {
            return response()->json([]);
        }

        // ID del resguardante actual para no mostrarse a sí mismo
        $currentResguardanteId = $currentUser->resguardante ? $currentUser->resguardante->id : null;

        $resguardantes = Resguardante::with('usuario') // Traemos la relación (puede ser null)
            ->where(function($q) use ($queryText) {
                
                // 1. Buscamos coincidencias en la tabla USUARIOS (si tiene)
                $q->whereHas('usuario', function($qu) use ($queryText) {
                    $qu->where('usuario_nombre', 'ILIKE', "%{$queryText}%")
                    ->orWhere('usuario_correo', 'ILIKE', "%{$queryText}%");
                })
                
                // 2. O buscamos coincidencias en la tabla RESGUARDANTES directamente
                // (Esto permite encontrar a Beatriz aunque no tenga usuario)
                // ¡IMPORTANTE!: Cambia 'res_nombre' por el nombre real de tu columna en la BD
                ->orWhere('res_nombre', 'ILIKE', "%{$queryText}%"); 
            })
            // Exclusión de uno mismo
            ->when($currentResguardanteId, function ($q) use ($currentResguardanteId) {
                return $q->where('id', '!=', $currentResguardanteId);
            })
            ->limit(5)
            ->get();

        $data = $resguardantes->map(function($res) {
            // Determinamos si tiene usuario válido
            $hasUser = $res->usuario ? true : false;
            
            // Obtenemos el nombre: Si tiene usuario, del usuario. Si no, del resguardante.
            // Ajusta 'res_nombre' según tu base de datos.
            $nombreMostrar = $hasUser ? $res->usuario->usuario_nombre : $res->res_nombre;

            return [
                'id' => $res->id,
                'nombre' => $nombreMostrar,
                'correo' => $hasUser ? $res->usuario->usuario_correo : 'Sin correo registrado',
                'cargo'  => $res->res_cargo ?? 'Sin cargo',
                // Iniciales
                'iniciales' => substr($nombreMostrar, 0, 2),
                
                // Esta bandera activa el mensaje rojo en el Frontend
                'tiene_usuario' => $hasUser
            ];
        });

        return response()->json($data);
    }

    /**
     * @OA\Get(
     * path="/mis-movimientos",
     * summary="Obtener mis movimientos físicos",
     * tags={"Perfil Resguardante"},
     * @OA\Response(
     * response=200,
     * description="Historial de movimientos iniciados por mi",
     * @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/MovimientoBien")))
     * )
     * )
     */
    public function misMovimientos(Request $request)
    {
        $user = $request->user();

        // Traemos los movimientos donde el usuario logueado fue quien realizó la acción (usuario_origen)
        $movimientos = MovimientoBien::where('movimiento_id_usuario_origen', $user->id)
            ->with([
                // 1. Datos del Bien y su Oficina Dueña (Origen)
                'bien.oficina', 
                // 2. Datos del Departamento Destino
                'departamento'
            ])
            ->orderBy('movimiento_fecha', 'desc') // Los más recientes primero
            ->paginate(15);

        return response()->json($movimientos);
    }

    /**
     * @OA\Get(
     * path="/mis-transferencias",
     * summary="Obtener mis traspasos (Origen/Destino)",
     * tags={"Perfil Resguardante"},
     * @OA\Response(
     * response=200,
     * description="Historial de solicitudes de traspaso",
     * @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Traspaso")))
     * )
     * )
     */
    public function misTransferencias(Request $request)
    {
        $user = $request->user();
        
        // Validamos que tenga perfil de resguardante
        if (!$user->resguardante) {
            return response()->json(['data' => []]);
        }
        
        $miResguardanteId = $user->resguardante->id;

        // Buscamos traspasos donde soy Origen O Destino
        $traspasos = Traspaso::with([
                'bien', 
                // Cargar datos del OTRO resguardante para saber con quién fue el trato
                'resguardanteOrigen.usuario', 
                'resguardanteDestino.usuario'
            ])
            ->where(function($q) use ($miResguardanteId) {
                $q->where('traspaso_id_usuario_origen', $miResguardanteId)
                ->orWhere('traspaso_id_usuario_destino', $miResguardanteId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($traspasos);
    }

    /**
     * @OA\Get(
     * path="/resguardante/dashboard",
     * summary="Dashboard del Resguardante",
     * description="Métricas rápidas y últimos movimientos para la pantalla de inicio del app móvil.",
     * tags={"Perfil Resguardante"},
     * @OA\Response(
     * response=200,
     * description="Datos del dashboard",
     * @OA\JsonContent(
     * @OA\Property(property="contadores", type="object",
     * @OA\Property(property="bienes", type="integer"),
     * @OA\Property(property="movimientos", type="integer"),
     * @OA\Property(property="transferencias", type="integer")
     * ),
     * @OA\Property(property="info", type="object",
     * @OA\Property(property="oficina", type="string"),
     * @OA\Property(property="departamento", type="string")
     * ),
     * @OA\Property(property="ultimos_movimientos", type="array", @OA\Items(ref="#/components/schemas/MovimientoBien"))
     * )
     * ),
     * @OA\Response(response=404, description="Perfil no encontrado")
     * )
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        
        if (!$user->resguardante) {
            return response()->json(['message' => 'Perfil no encontrado'], 404);
        }

        $resguardanteId = $user->resguardante->id;
        $userId = $user->id;

        // 1. Contadores
        $totalBienes = \App\Models\Bien::where('id_resguardante', $resguardanteId)->count();
        
        // Movimientos (físicos) hechos por el usuario
        $totalMovimientos = \App\Models\MovimientoBien::where('movimiento_id_usuario_origen', $userId)->count();
        
        // Transferencias (Traspasos) donde participa (origen o destino)
        $totalTransferencias = \App\Models\Traspaso::where('traspaso_id_usuario_origen', $resguardanteId)
            ->orWhere('traspaso_id_usuario_destino', $resguardanteId)
            ->count();

        // 2. Información de Ubicación (Oficina/Depto)
        // Cargamos relaciones del resguardante
        $user->resguardante->load(['oficina', 'departamento']);

        // 3. Últimos 5 movimientos para la tabla
        $ultimosMovimientos = \App\Models\MovimientoBien::with(['bien', 'departamento'])
            ->where('movimiento_id_usuario_origen', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'contadores' => [
                'bienes' => $totalBienes,
                'movimientos' => $totalMovimientos,
                'transferencias' => $totalTransferencias
            ],
            'info' => [
                'oficina' => $user->resguardante->oficina ? $user->resguardante->oficina->nombre : 'Sin asignar',
                'departamento' => $user->resguardante->departamento ? $user->resguardante->departamento->dep_nombre : 'Sin asignar',
            ],
            'ultimos_movimientos' => $ultimosMovimientos
        ]);
    }
}