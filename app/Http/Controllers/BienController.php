<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Oficina;
use App\Models\MovimientoBien;
use App\Models\Resguardante;

use App\Events\BienEstadoActualizado;
use App\Events\NuevoMovimientoRegistrado;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;
use Throwable;

/**
 * @OA\Tag(
 * name="Bienes",
 * description="Endpoints para la gestión de los bienes del inventario"
 * )
 */
class BienController extends Controller
{
    /**
     * @OA\Get(
     * path="/bienes",
     * summary="Listar bienes con filtros",
     * description="Obtiene una lista paginada de bienes. Requiere enviar 'search' O 'id_oficina'.",
     * tags={"Bienes"},
     * @OA\Parameter(
     * name="search",
     * in="query",
     * description="Término de búsqueda (código, serie, descripción)",
     * required=false,
     * @OA\Schema(type="string", minLength=3)
     * ),
     * @OA\Parameter(
     * name="id_oficina",
     * in="query",
     * description="Filtrar por ID de oficina",
     * required=false,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Parameter(
     * name="page",
     * in="query",
     * description="Número de página para paginación",
     * required=false,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Lista de bienes paginada",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="array", @OA\Items(
     * type="object",
     * @OA\Property(property="id", type="integer", example=10),
     * @OA\Property(property="bien_codigo", type="string", example="TI-2024-001"),
     * @OA\Property(property="bien_descripcion", type="string", example="Laptop Dell"),
     * @OA\Property(property="oficina", type="object", description="Datos de la oficina y ubicación")
     * )),
     * @OA\Property(property="current_page", type="integer", example=1),
     * @OA\Property(property="total", type="integer", example=50)
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Error: Falta filtro de búsqueda"
     * )
     * )
     */
    public function index(Request $request)
    {
        // Valida que el frontend esté pidiendo un filtro
        $request->validate([
            'search' => 'nullable|string|min:3',
            'id_oficina' => 'nullable|integer|exists:oficinas,id'
        ]);

        $query = Bien::with([
            'oficina:id,nombre,id_departamento,id_edificio',
            'oficina.departamento:id,dep_nombre,id_area',
            'oficina.departamento.area:id,area_nombre',
            'ubicacionActual:id,nombre',
            'resguardos' => function ($q) {
                $q->latest('resguardo_fecha_asignacion')->limit(1)->with('resguardante:id,res_nombre,res_apellidos');
            }
        ]);

        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function($q) use ($term) {
                $q->where('bien_codigo', 'like', "%{$term}%")
                  ->orWhere('bien_serie', 'like', "%{$term}%")
                  ->orWhere('bien_descripcion', 'like', "%{$term}%")
                  ->orWhere('bien_clave', 'like', "%{$term}%");
            });
        } 
        else if ($request->filled('id_oficina')) {
            $query->where('id_oficina', $request->input('id_oficina'));
        } 
        else {
             return response()->json(['error' => 'Se requiere un filtro de búsqueda o de oficina.'], 400);
        }

        $bienes = $query->select(
                'id', 'bien_codigo', 'bien_descripcion', 'bien_caracteristicas','bien_serie', 
                'bien_marca', 'bien_modelo', 'bien_estado', 'id_oficina', 'bien_provedor', 'bien_tipo_adquisicion', 'bien_numero_factura', 'bien_valor_monetario', 'bien_ubicacion_actual'
            )
            ->orderBy('id', 'desc')
            ->paginate(25);

        return $bienes;
    }

    /**
     * @OA\Post(
     * path="/bienes",
     * summary="Crear nuevos bienes (Lote o Unitario)",
     * description="Crea uno o varios bienes basados en el campo 'cantidad'. Genera códigos secuenciales automáticamente.",
     * tags={"Bienes"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"bien_descripcion", "bien_valor_monetario", "bien_clave", "bien_y", "cantidad"},
     * @OA\Property(property="id_oficina", type="integer", example=5),
     * @OA\Property(property="bien_descripcion", type="string", example="Monitor 24 pulgadas"),
     * @OA\Property(property="bien_valor_monetario", type="number", format="float", example=3500.50),
     * @OA\Property(property="bien_clave", type="string", example="MOB-01", description="Prefijo para el código"),
     * @OA\Property(property="bien_y", type="string", example="2024", description="Año del bien"),
     * @OA\Property(property="cantidad", type="integer", example=5, description="Cuántos bienes idénticos crear"),
     * @OA\Property(property="bien_serie", type="string", example="SN-123456", description="Opcional, serie del fabricante"),
     * @OA\Property(property="bien_marca", type="string", example="Samsung"),
     * @OA\Property(property="bien_modelo", type="string", example="S24F350")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Bienes creados exitosamente",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Bienes registrados correctamente"),
     * @OA\Property(property="cantidad", type="integer", example=5),
     * @OA\Property(property="data", type="array", @OA\Items(type="object", description="Lista de bienes creados"))
     * )
     * )
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id_oficina' => 'nullable|integer|exists:oficinas,id',
            'bien_modelo' => 'nullable|string|max:255',
            'bien_serie' => 'nullable|string|max:255',
            'bien_descripcion' => 'required|string',
            'bien_caracteristicas' => 'nullable|string',
            'bien_tipo_adquisicion' => 'required|string|max:255',
            'bien_fecha_alta' => 'nullable|date',
            'bien_valor_monetario' => 'required|numeric|min:0',
            'bien_provedor' => 'nullable|string|max:255',
            'bien_numero_factura' => 'nullable|string|max:255',
            'bien_estado' => 'nullable|string|max:255',
            'bien_marca' => 'nullable|string|max:255',
            'bien_clave' => 'required|string|max:255',
            'bien_y' => 'required|string|max:4',
            'bien_sec_alfabetica' => 'nullable|string|max:5',
            'cantidad' => 'required|integer|min:1',
        ]);

        $bienesCreados = [];
        $claveCamb = $validatedData['bien_clave'];
        $anio = $validatedData['bien_y'];
        $cantidad = (int)$validatedData['cantidad'];

        $baseData = $validatedData;
        unset($baseData['cantidad']);

        $baseData['bien_ubicacion_actual'] = $validatedData['id_oficina'] ?? null;

        try {
            DB::beginTransaction();

            $ultimoBien = Bien::where('bien_clave', $claveCamb)
                                ->where('bien_y', $anio)
                                ->orderByRaw('CAST(bien_secuencia AS INTEGER) DESC')
                                ->lockForUpdate()
                                ->first();

            $siguienteSecuenciaNum = $ultimoBien ? (int)$ultimoBien->bien_secuencia + 1 : 1;
            $componente_anio = substr($anio, -2);
            $componente_instituto = '23';
            $movimientoIdDep = null;
            if (!empty($validatedData['id_oficina'])) {
                $oficina = Oficina::find($validatedData['id_oficina']);
                $movimientoIdDep = $oficina ? $oficina->id_departamento : null;
            }

            for ($i = 0; $i < $cantidad; $i++) {
                
                $secuenciaActualNum = $siguienteSecuenciaNum + $i;
                $componente_secuencia_str = str_pad($secuenciaActualNum, 5, '0', STR_PAD_LEFT);

                $nuevoCodigo = "{$claveCamb}-{$componente_anio}-{$componente_instituto}-{$componente_secuencia_str}";

                $dataParaEsteBien = $baseData;
                $dataParaEsteBien['bien_codigo'] = $nuevoCodigo;
                $dataParaEsteBien['bien_secuencia'] = (string)$secuenciaActualNum;
                $dataParaEsteBien['bien_serie'] = $validatedData['bien_serie'] ?? 'SIN SERIE';

                $bien = Bien::create($dataParaEsteBien);
                $bienesCreados[] = $bien;

                MovimientoBien::create([
                    'movimiento_id_bien'        => $bien->id,
                    'movimiento_id_dep'         => $movimientoIdDep, 
                    'movimiento_fecha'          => now(),
                    'movimiento_tipo'           => 'ALTA', 
                    'movimiento_id_usuario_origen' => Auth::id(), 
                    'movimiento_id_usuario_destino' => Auth::id(), 
                    'movimiento_id_usuario_autorizado' => Auth::id(),
                    'movimiento_observaciones'  => 'Alta inicial de bien por lote o unitario.',
                ]);
            }

            DB::commit();
            if (count($bienesCreados) > 0) {
                $bienesCreados[0]->load('oficina.departamento.area');
            }

            return response()->json([
                'message' => 'Bienes registrados correctamente',
                'cantidad' => $cantidad,
                'data'    => $bienesCreados
            ], 201);

        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al crear el lote de bienes. La operación fue revertida.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     * path="/bienes/{id}",
     * summary="Obtener detalles de un bien",
     * description="Retorna toda la información del bien, incluyendo historial formateado de resguardos y ubicaciones.",
     * tags={"Bienes"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Detalles del bien",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="bien_codigo", type="string", example="TI-001"),
     * @OA\Property(property="resguardos", type="array", description="Historial procesado de resguardos", @OA\Items(
     * @OA\Property(property="resguardo_fecha_asignacion", type="string", format="date"),
     * @OA\Property(property="res_nombre", type="string"),
     * @OA\Property(property="res_apellidos", type="string")
     * )),
     * @OA\Property(property="movimientos_bien", type="array", description="Historial de cambios de ubicación", @OA\Items(
     * @OA\Property(property="movimiento_fecha", type="string", format="date-time"),
     * @OA\Property(property="destino_oficina", type="string")
     * ))
     * )
     * ),
     * @OA\Response(response=404, description="Bien no encontrado")
     * )
     */
    public function show(Bien $biene)
    {
            // Cargar relaciones necesarias
            $biene->load([
                'oficina.departamento.area',
                'oficina.edificio',
                'resguardos.resguardante',      
                'movimientosBien.departamento', 
                'traspasos.usuarioOrigen'
            ]);

            //  Procesar HISTORIAL DE RESGUARDOS

            $historialResguardos = $biene->resguardos->map(function ($resguardo) {
                return [
                    'resguardo_fecha_asignacion' => $resguardo->resguardo_fecha_asignacion,
                    'res_nombre'    => $resguardo->resguardante ? $resguardo->resguardante->res_nombre : 'Sin nombre',
                    'res_apellidos' => $resguardo->resguardante ? $resguardo->resguardante->res_apellidos : '',
                ];
            });

            // Procesar HISTORIAL DE UBICACIONES

            $historialUbicaciones = $biene->movimientosBien
                ->filter(function ($movimiento) {
                    return $movimiento->movimiento_tipo === 'MOVIMIENTO';
                })
                ->map(function ($movimiento) {
                    return [
                        'movimiento_fecha' => $movimiento->movimiento_fecha,
                        'destino_oficina'  => $movimiento->departamento ? $movimiento->departamento->dep_nombre : 'Ubicación Desconocida',
                    ];
                })
                ->values(); 
            $respuesta = $biene->toArray();
            
            // Reemplazamos los arrays "sucios" por los filtrados
            $respuesta['resguardos'] = $historialResguardos;
            $respuesta['movimientos_bien'] = $historialUbicaciones;


            return response()->json($respuesta);
    }
    /**
     * @OA\Put(
     * path="/bienes/{id}",
     * summary="Actualizar un bien (Polimórfico)",
     * description="Permite editar info, dar de baja, mover, reactivar o regresar resguardo basado en el campo 'accion'.",
     * tags={"Bienes"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"accion"},
     * @OA\Property(property="accion", type="string", enum={"editar_info", "baja", "mover", "reactivar", "regresar"}, description="Define qué lógica ejecutar"),
     * @OA\Property(property="nuevo_id_oficina", type="integer", description="Requerido para acciones 'mover' o 'reactivar'"),
     * @OA\Property(property="bien_descripcion", type="string", description="Para editar_info"),
     * @OA\Property(property="bien_estado", type="string", description="Para editar_info"),
     * @OA\Property(property="id_resguardante", type="integer", description="Para editar_info")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Operación exitosa",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Información actualizada"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=403, description="No tienes permisos para mover este bien"),
     * @OA\Response(response=409, description="Conflicto (ej. el bien tiene resguardo activo y no se puede mover)")
     * )
     */
    public function update(Request $request, Bien $biene)
    {
        // Si no envían 'accion', asumimos que es una edición normal.
        $accion = $request->input('accion', 'editar_info');
        $user = $request->user();

        return match ($accion) {
            'baja'   => $this->procesarBaja($request, $biene),
            'mover'     => $this->determinarTipoMovimiento($request, $biene, $user),
            'reactivar' => $this->procesarReactivacion($request, $biene),
            'regresar'  => $this->procesarRegresoResguardante($request, $biene),
            default  => $this->procesarEdicionNormal($request, $biene),
        };
        
    }
    protected function determinarTipoMovimiento($request, $bien, $user)
    {

        if ($user->rol && $user->rol->nombre === 'Administrador') {
            return $this->procesarMovimiento($request, $bien);
        }

        return $this->procesarMovimientoResguardante($request, $bien);
    }

    protected function procesarBaja(Request $request, Bien $biene)
    {
        $resguardanteId = $biene->id_resguardante;

        DB::transaction(function () use ($request, $biene, $resguardanteId) {
                //Actualizar estado del bien
                $biene->update([
                    'bien_estado' => 'Baja',
                    'id_resguardante' => null,
                    'bien_ubicacion_actual' => 9,
                    'id_oficina' => 9,
                ]);

                // Obtenemos el usuario autenticado (el que realiza la baja)
                $userId = auth()->id(); // O $request->user()->id

                $movimientoIdDep = null;
                if (!empty($biene['id_oficina'])) {
                    $oficina = Oficina::find($biene['id_oficina']);
                    $movimientoIdDep = $oficina ? $oficina->id_departamento : null;
                }
                MovimientoBien::create([
                    'movimiento_id_bien' => $biene->id,
                    'movimiento_id_dep' => $movimientoIdDep,
                    'movimiento_fecha' => now(),
                    'movimiento_tipo' => 'BAJA',
                    'movimiento_cantidad' => 1,
                    'movimiento_id_usuario_origen' => $userId,
                    'movimiento_id_usuario_destino' => $userId, 
                    'movimiento_id_usuario_autorizado' => $userId, 
                    'movimiento_observaciones' => $request->input('observaciones', 'Baja de bien'),
                ]);
            });

            return response()->json([
                'message' => 'El bien ha sido dado de baja y el movimiento registrado correctamente.',
                'data' => $biene,
                'resguardante_afectado_id' => $resguardanteId
        ]);
    }

    protected function procesarMovimiento(Request $request, Bien $biene)
    {

        if ($biene->resguardos()->exists()) {
            return response()->json([
                'message' => 'No se puede mover el bien porque tiene un resguardo asignado. Debe liberar el resguardo primero.'
            ], 409); 
        }

        $datos = $request->validate([
            'nuevo_id_oficina' => 'required|exists:oficinas,id',
        ]);

        $biene->update([
            'id_oficina' => $datos['nuevo_id_oficina'],            
            'bien_ubicacion_actual' => $datos['nuevo_id_oficina'], 
            'bien_estado' => 'Activo' 
        ]);

        return response()->json([
            'message' => 'Bien reubicado correctamente y ubicación física actualizada.', 
            'data' => $biene
        ]);
    }
    protected function procesarMovimientoResguardante(Request $request, Bien $biene)
    {
        $datos = $request->validate([
            'nuevo_id_oficina' => 'required|exists:oficinas,id',
        ]);

        $user = $request->user();
        if ($user->resguardante && $biene->id_resguardante !== $user->resguardante->id) {
            return response()->json(['message' => 'No tienes permiso para mover este bien.'], 403);
        }

        // Obtener datos necesarios para el historial
        $nuevaOficina = Oficina::findOrFail($datos['nuevo_id_oficina']);

        //Crear el Registro en la tabla movimientos_bien
        $nuevoMovimiento = MovimientoBien::create([
            'movimiento_id_bien' => $biene->id,
            'movimiento_id_dep'  => $nuevaOficina->id_departamento, 
            'movimiento_fecha'   => Carbon::now(),
            'movimiento_tipo'    => 'MOVIMIENTO', // O el código que uses
            'movimiento_cantidad'=> 1,
            'movimiento_id_usuario_origen' => $user->id,
            'movimiento_id_usuario_destino' => $user->id, 
            'movimiento_id_usuario_autorizado' => $user->id,
            'movimiento_observaciones' => 'Movimiento físico realizado por resguardante (En tránsito).',
        ]);
        $biene->update([
            'bien_ubicacion_actual' => $datos['nuevo_id_oficina'],
            'bien_estado' => 'En tránsito' 
        ]);

        broadcast(new NuevoMovimientoRegistrado($nuevoMovimiento))->toOthers();

    
        return response()->json([
            'message' => 'Movimiento registrado. El bien ahora está en tránsito a la nueva ubicación física.',
            'data' => $biene
        ]);
    }
    protected function procesarRegresoResguardante(Request $request, Bien $biene)
    {
        if ($biene->bien_estado !== 'En tránsito') {
            return response()->json(['message' => 'El bien no está en tránsito.'], 400);
        }

        $biene->update([
            'bien_ubicacion_actual' => $biene->id_oficina, 
            'bien_estado' => 'Activo'
        ]);

        return response()->json([
            'message' => 'El bien ha regresado a su ubicación original.',
            'data' => $biene
        ]);
    }

    protected function procesarEdicionNormal(Request $request, Bien $biene)
    {

        $datos = $request->validate([
            'bien_descripcion'      => 'sometimes|required|string',
            'bien_caracteristicas'  => 'sometimes|nullable|string',
            'bien_marca'            => 'sometimes|nullable|string|max:255',
            'bien_modelo'           => 'sometimes|nullable|string|max:255',
            'bien_serie'            => 'sometimes|nullable|string|max:255',
            'bien_sec_alfabetica'            => 'sometimes|nullable|string|max:255',
            'bien_estado'           => 'sometimes|required|string|max:50',
            'id_oficina'            => 'sometimes|required|integer|exists:oficinas,id',
            'id_resguardante'       => 'sometimes|nullable|integer|exists:resguardantes,id',
            'bien_provedor'         => 'sometimes|nullable|string|max:255', 
            'bien_fecha_alta'       => 'sometimes|nullable|date',

        ]);
        $biene->update($datos);
        $biene->refresh();

        return response()->json([
            'message' => 'Información del bien actualizada correctamente', 
            'data' => $biene
        ]);
    }

    protected function procesarReactivacion(Request $request, Bien $biene)
    {
        $datos = $request->validate([
            'id_oficina' => 'required|exists:oficinas,id', // Oficina Física seleccionada
            'id_resguardante' => 'nullable|exists:resguardantes,id',
        ]);

        DB::transaction(function () use ($biene, $datos) {
            
            // --- LÓGICA DE TRÁNSITO vs ACTIVO ---
            $nuevoEstado = 'Activo';
            $idOficinaDueño = $datos['id_oficina']; // Por defecto, el bien pertenece donde está físicamente
            $ubicacionFisica = $datos['id_oficina'];
            $observacionesMov = 'Reactivación de bien.';

            // Si se seleccionó un resguardante, verificamos su oficina
            if (!empty($datos['id_resguardante'])) {
                $resguardante = Resguardante::find($datos['id_resguardante']);
                
                if ($resguardante) {
                    // CASO DISCREPANCIA: Resguardante es de otra oficina
                    if ($resguardante->id_oficina != $datos['id_oficina']) {
                        $nuevoEstado = 'En tránsito';
                        $idOficinaDueño = $resguardante->id_oficina; // El bien pertenece a la oficina del resguardante
                        $ubicacionFisica = $datos['id_oficina']; // Pero físicamente está donde dijimos en el modal
                        $observacionesMov = 'Reactivación: Resguardante externo. Bien en tránsito hacia oficina del resguardante.';
                    } else {
                        $observacionesMov = 'Reactivación con asignación de resguardo.';
                    }
                }
            }

            // 1. Actualizar el bien
            $biene->update([
                'bien_estado' => $nuevoEstado,
                'id_oficina' => $idOficinaDueño,           // A quién pertenece (Inventarialmente)
                'bien_ubicacion_actual' => $ubicacionFisica, // Dónde está (Físicamente)
                'id_resguardante' => $datos['id_resguardante'] ?? null,
            ]);

            // 2. Registrar movimiento
            $userId = auth()->id();
            
            // Obtenemos el departamento asociado a la ubicación FÍSICA para el historial
            $movimientoIdDep = null;
            if (!empty($ubicacionFisica)) {
                $oficinaFisica = Oficina::find($ubicacionFisica);
                $movimientoIdDep = $oficinaFisica ? $oficinaFisica->id_departamento : null;
            }

            MovimientoBien::create([
                'movimiento_id_bien' => $biene->id,
                'movimiento_id_dep' => $movimientoIdDep,
                'movimiento_fecha' => now(),
                'movimiento_tipo' => 'Reactivación', // O podrías poner 'Reactivación (Tránsito)' si prefieres
                'movimiento_cantidad' => 1,
                'movimiento_id_usuario_origen' => $userId,
                'movimiento_id_usuario_destino' => $userId,
                'movimiento_id_usuario_autorizado' => $userId,
                'movimiento_observaciones' => $observacionesMov,
            ]);
        });

        $biene->refresh();

        return response()->json([
            'message' => 'El bien ha sido procesado correctamente.',
            'data' => $biene,
            'resguardante_asignado_id' => $datos['id_resguardante'] ?? null
        ]);
    }

    /**
     * @OA\Delete(
     * path="/bienes/{id}",
     * summary="Eliminar un bien",
     * tags={"Bienes"},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(response=204, description="Bien eliminado correctamente"),
     * @OA\Response(response=409, description="No se puede eliminar (tiene registros asociados)"),
     * @OA\Response(response=500, description="Error del servidor")
     * )
     */
    public function destroy(Bien $biene)
    {
        try {
            $deleted = $biene->delete();
            if ($deleted) {
                return response()->json(null, 204);
            }
            return response()->json(['error' => 'No se pudo eliminar el registro.'], 500);

        } catch (QueryException $e) {
            return response()->json(['error' => 'No se puede eliminar, tiene registros asociados.','message' => $e->getMessage()], 409);

        } catch (Throwable $e) {
            return response()->json(['error' => 'Error al eliminar el bien.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     * path="/bienes/comparar-inventario",
     * summary="Comparar inventario físico vs sistema",
     * description="Recibe una lista de códigos escaneados y devuelve encontrados, faltantes y sobrantes.",
     * tags={"Inventario"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"id_oficina", "claves_escaneadas"},
     * @OA\Property(property="id_oficina", type="integer", example=10),
     * @OA\Property(property="claves_escaneadas", type="array", @OA\Items(type="string", example="TI-2024-001"))
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Resultado de la comparación",
     * @OA\JsonContent(
     * @OA\Property(property="resumen", type="object",
     * @OA\Property(property="total_esperados", type="integer"),
     * @OA\Property(property="conteo_faltantes", type="integer")
     * ),
     * @OA\Property(property="faltantes", type="array", @OA\Items(type="object")),
     * @OA\Property(property="sobrantes", type="array", @OA\Items(type="object")),
     * @OA\Property(property="encontrados", type="array", @OA\Items(type="object"))
     * )
     * )
     * )
     */
    public function compararInventario(Request $request)
    {
        $idOficina = $request->input('id_oficina');
        $escaneados = collect($request->input('claves_escaneadas'));

        $relacionesBase = [
            'oficina.departamento:id,dep_nombre',
            'ubicacionActual:id,nombre' 
        ];

        $bienesTeoricos = Bien::with($relacionesBase)
                            ->where('id_oficina', $idOficina)
                            ->get();
                            
        $clavesTeoricas = $bienesTeoricos->pluck('bien_codigo');

        // ENCONTRADOS
        $encontrados = $bienesTeoricos->whereIn('bien_codigo', $escaneados)
                                      ->values()
                                      // Ocultamos fechas Y la llave foránea redundante
                                      ->makeHidden(['created_at', 'updated_at', 'bien_ubicacion_actual']);
        // FALTANTES
        $faltantes = $bienesTeoricos->whereNotIn('bien_codigo', $escaneados)
                                    ->values()
                                    ->makeHidden(['created_at', 'updated_at', 'bien_ubicacion_actual']);

        // SOBRANTES
        $clavesSobrantes = $escaneados->diff($clavesTeoricas);
        
        $sobrantesRaw = Bien::whereIn('bien_codigo', $clavesSobrantes)
                            ->with([
                                'oficina:id,nombre,id_departamento', 
                                'ubicacionActual:id,nombre', 
                                'resguardos.resguardante:id,res_nombre,res_apellidos,res_departamento', 
                                'resguardos.resguardante.departamento:id,dep_nombre' 
                            ])
                            ->get();

        $sobrantesLimpios = $sobrantesRaw->map(function ($bien) {
            $resguardo = $bien->resguardos->first(); 
            $resguardante = $resguardo ? $resguardo->resguardante : null;
            
            $nombreCompleto = $resguardante 
                ? trim(($resguardante->res_nombre ?? '') . ' ' . ($resguardante->res_apellidos ?? ''))
                : 'Sin Resguardante';

            return [
                'id' => $bien->id,
                'codigo' => $bien->bien_codigo,
                'descripcion' => $bien->bien_descripcion,
                'resguardante' => $nombreCompleto,
                'departamento_resguardante' => $resguardante?->departamento?->dep_nombre ?? 'N/A',
                'oficina_pertenencia' => $bien->oficina?->nombre ?? 'Sin Oficina',
                'ubicacion_actual' => $bien->ubicacionActual?->nombre ?? 'No definida'
            ];
        });

        return response()->json([
            'resumen' => [
                'total_esperados'    => $bienesTeoricos->count(),
                'total_escaneados'   => $escaneados->count(),
                'conteo_encontrados' => $encontrados->count(),
                'conteo_faltantes'   => $faltantes->count(),
                'conteo_sobrantes'   => $sobrantesLimpios->count(),
            ],
            'encontrados' => $encontrados, 
            'faltantes'   => $faltantes, 
            'sobrantes'   => $sobrantesLimpios, 
        ]);
    }
    /**
     * @OA\Get(
     * path="/bienes/bajas",
     * summary="Listar bienes dados de baja",
     * tags={"Bienes"},
     * @OA\Response(
     * response=200,
     * description="Lista de bienes inactivos",
     * @OA\JsonContent(
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="cantidad", type="integer"),
     * @OA\Property(property="data", type="array", @OA\Items(type="object"))
     * )
     * )
     * )
     */
    public function bajas(Request $request)
    {
        // Iniciamos la consulta filtrando por estado 'Baja'
        $query = Bien::where('bien_estado', 'Baja')
                    ->with(['oficina', 'resguardos']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('bien_codigo', 'LIKE', "%{$search}%")
                ->orWhere('bien_descripcion', 'LIKE', "%{$search}%")
                ->orWhere('bien_marca', 'LIKE', "%{$search}%")
                ->orWhere('bien_modelo', 'LIKE', "%{$search}%");
            });
        }

        $bienesBaja = $query->orderBy('updated_at', 'desc')->paginate(10);

        return response()->json($bienesBaja);
    }
    /**
     * @OA\Get(
     * path="/bienes/buscar-codigo/{codigo}",
     * summary="Buscar bien por código exacto o serie",
     * tags={"Bienes"},
     * @OA\Parameter(name="codigo", in="path", required=true, @OA\Schema(type="string")),
     * @OA\Response(
     * response=200,
     * description="Bien encontrado",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Bien encontrado"),
     * @OA\Property(property="data", type="object")
     * )
     * ),
     * @OA\Response(response=404, description="No se encontró ningún bien"),
     * @OA\Response(response=409, description="El bien existe pero está dado de BAJA")
     * )
     */
    public function buscarPorCodigo($codigo)
    {
        // Limpiamos el input por si viene con espacios accidentales
        $codigo = trim($codigo);

        // Buscamos coincidencia exacta en código o serie
        $bien = Bien::where('bien_codigo', $codigo)
                    ->orWhere('bien_serie', $codigo)
                    ->with(['oficina']) // Cargamos la oficina para contexto visual
                    ->first();

        if (!$bien) {
            return response()->json([
                'message' => 'No se encontró ningún bien con ese código o serie.'
            ], 404);
        }

        if ($bien->bien_estado === 'Baja') {
             return response()->json([
                'message' => 'El bien existe pero está dado de BAJA.',
                'data' => $bien
            ], 409); 
        }

        return response()->json([
            'message' => 'Bien encontrado',
            'data' => $bien
        ], 200);
    }
    /**
     * @OA\Post(
     * path="/bienes/procesar-levantamiento",
     * summary="Procesar resultados del levantamiento",
     * description="Actualiza masivamente los bienes encontrados, faltantes y sobrantes tras una auditoría.",
     * tags={"Inventario"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"id_oficina_levantamiento"},
     * @OA\Property(property="id_oficina_levantamiento", type="integer"),
     * @OA\Property(property="encontrados", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="accion", type="string", enum={"ACTIVO", "EN_TRANSITO", "EXTRAVIADO"}),
     * @OA\Property(property="bien_descripcion", type="string", description="Para corregir datos al vuelo")
     * )),
     * @OA\Property(property="faltantes", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="accion", type="string", enum={"EXTRAVIADO", "ACTIVO"})
     * )),
     * @OA\Property(property="sobrantes", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="accion", type="string", enum={"ACTUALIZAR_AQUI", "REGRESAR_ORIGEN"})
     * ))
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Levantamiento procesado correctamente",
     * @OA\JsonContent(@OA\Property(property="message", type="string"))
     * ),
     * @OA\Response(response=500, description="Error en la transacción")
     * )
     */
    public function procesarLevantamiento(Request $request)
    {
        $request->validate([
            'id_oficina_levantamiento' => 'required|exists:oficinas,id',
            
            'encontrados' => 'array',
            'encontrados.*.id' => 'required|exists:bienes,id',
            'encontrados.*.bien_marca' => 'nullable|string|max:100',
            'encontrados.*.bien_modelo' => 'nullable|string|max:100',
            'encontrados.*.bien_serie' => 'nullable|string|max:100',
            'encontrados.*.bien_descripcion' => 'nullable|string|max:255',
            'encontrados.*.bien_caracteristicas' => 'nullable|string',

            'faltantes' => 'array',
            'faltantes.*.id' => 'required|exists:bienes,id',
            
            'sobrantes' => 'array',
            'sobrantes.*.id' => 'required|exists:bienes,id',
        ]);

        $idOficinaFisica = $request->input('id_oficina_levantamiento');

        try {
            DB::transaction(function () use ($request, $idOficinaFisica) {
                
                // BIENES ENCONTRADOS
                $encontrados = $request->input('encontrados', []);
                
                foreach ($encontrados as $item) {
                    $bien = Bien::find($item['id']);
                    if (!$bien) continue;

                    // Determinamos el estado y ubicación según la "accion"
                    $accion = $item['accion'] ?? 'ACTIVO'; 

                    switch ($accion) {
                        case 'EN_TRANSITO':
                            $bien->bien_estado = 'En tránsito';
                            // Si se define destino, se actualiza, si no, se queda donde se encontró
                            if (isset($item['id_oficina_destino'])) {
                                $bien->bien_ubicacion_actual = $item['id_oficina_destino'];
                            } else {
                                $bien->bien_ubicacion_actual = $idOficinaFisica;
                            }
                            break;

                        case 'EXTRAVIADO':
                            $bien->bien_estado = 'Extraviado';
                            $bien->bien_ubicacion_actual = $idOficinaFisica;
                            break;

                        case 'ACTIVO':
                        default:
                            $bien->bien_estado = 'Activo';
                            $bien->bien_ubicacion_actual = $idOficinaFisica;
                            break;
                    }

                    // Actualización de información (Edición)
                    // Filtramos solo los campos que coinciden con la BD
                    $datosEditables = Arr::only($item, [
                        'bien_marca', 
                        'bien_modelo', 
                        'bien_serie', 
                        'bien_descripcion', 
                        'bien_caracteristicas'
                    ]);
                    if (!empty($datosEditables)) {
                        $bien->fill($datosEditables);
                    }

                    $bien->save();
                }
                // BIENES FALTANTES
                $faltantes = $request->input('faltantes', []);
                
                foreach ($faltantes as $item) {
                    $bien = Bien::find($item['id']);
                    if (!$bien) continue;
                    
                    $accion = $item['accion'] ?? 'EXTRAVIADO';
                    
                    switch ($accion) {
                        case 'EN_TRANSITO':
                            $bien->bien_estado = 'En tránsito';
                            if (isset($item['id_oficina_destino'])) {
                                $bien->bien_ubicacion_actual = $item['id_oficina_destino'];
                            }
                            break;
                            
                        case 'ACTIVO': // Apareció de nuevo
                            $bien->bien_estado = 'Activo';
                            $bien->bien_ubicacion_actual = $idOficinaFisica; 
                            break;
                            
                        case 'EXTRAVIADO':
                        default:
                            $bien->bien_estado = 'Extraviado';
                            break;
                    }
                    $bien->save();
                }

                // BIENES SOBRANTES
                $sobrantes = $request->input('sobrantes', []);
                
                foreach ($sobrantes as $item) {
                    $bien = Bien::find($item['id']);
                    if (!$bien) continue;

                    // VALIDACIÓN DE RESGUARDO 
                    // Verifica que la relación 'resguardos' exista en tu modelo Bien.
                    // Si el nombre de la relación es diferente, ajústalo aquí.
                    $tieneResguardoActivo = false;
                    
                    // Descomenta y ajusta según tu lógica real:
                    /* $tieneResguardoActivo = $bien->resguardos()
                        ->where('estado', 'Activo') // Asumiendo campo 'estado'
                        ->exists();
                    */

                    if ($tieneResguardoActivo) {
                        // Si tiene resguardo, NO movemos el bien.
                        continue; 
                    }

                    $accion = $item['accion'] ?? '';

                    if ($accion === 'ACTUALIZAR_AQUI') {
                        // Se adopta en la oficina actual
                        $bien->bien_ubicacion_actual = $idOficinaFisica;
                        $bien->bien_estado = 'Activo';
                    } elseif ($accion === 'REGRESAR_ORIGEN') {
                        // Vuelve a su dueño original (si existe dato en id_oficina)
                        if ($bien->id_oficina) {
                            $bien->bien_ubicacion_actual = $bien->id_oficina;
                            $bien->bien_estado = 'Activo'; 
                        }
                    }
                    
                    $bien->save();
                }

            });

            event(new BienEstadoActualizado(null, 'ACTUALIZACION_MASIVA', $idOficinaFisica));

            return response()->json(['message' => 'Levantamiento procesado correctamente.']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al procesar el levantamiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Post(
     * path="/bienes/{id}/foto",
     * summary="Subir foto del bien",
     * tags={"Bienes"},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="multipart/form-data",
     * @OA\Schema(
     * @OA\Property(property="imagen", type="string", format="binary", description="Archivo de imagen (jpg, png)")
     * )
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Imagen actualizada",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string"),
     * @OA\Property(property="foto_url", type="string")
     * )
     * )
     * )
     */
    public function subirFoto(Request $request, $id)
    {
        $request->validate([
            'imagen' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Máx 2MB
        ]);

        $bien = Bien::findOrFail($id);

        if ($request->hasFile('imagen')) {
            if ($bien->bien_foto && Storage::disk('public')->exists($bien->bien_foto)) {
                Storage::disk('public')->delete($bien->bien_foto);
            }
            $path = $request->file('imagen')->store('bienes', 'public');

            $bien->bien_foto = $path;
            $bien->save();
        }

        return response()->json([
            'message' => 'Imagen actualizada',
            'foto_url' => $bien->foto_url 
        ]);
    }
    public function getFotoUrlAttribute()
    {
        if ($this->bien_foto) {
            return asset('storage/' . $this->bien_foto);
        }
        
        return null; 
    }
    protected $appends = ['foto_url'];

    public function getBienesActivosPorResguardante($id)
    {
        // Buscamos directamente por ID de resguardante y Estado Activo
        $bienes = Bien::where('id_resguardante', $id)
                    ->where('bien_estado', 'Activo') // Solo queremos lo que sigue vigente
                    ->with(['oficina', 'resguardos']) // Cargamos relaciones necesarias para el PDF
                    ->orderBy('updated_at', 'desc')
                    ->get(); // Usamos get() para traer TODOS sin paginación

        return response()->json([
            'success' => true,
            'data' => $bienes
        ]);
    }

    public function bienesPorDepartamento(Request $request)
    {
        $user = $request->user();

        // 1. Validar Rol (Solo Jefes de Depto - ID 4)
        if ($user->usuario_id_rol !== 4) {
            return response()->json(['message' => 'No tienes permisos de Jefe de Departamento.'], 403);
        }

        if (!$user->resguardante || !$user->resguardante->oficina) {
            return response()->json(['message' => 'Tu usuario no tiene una oficina/departamento asociado.'], 403);
        }

        // 2. Obtener el ID del Departamento del Jefe
        // Asumimos: Jefe -> Resguardante -> Oficina -> Departamento
        $departamentoId = $user->resguardante->oficina->id_departamento;

        // 3. Consulta
        $query = Bien::query()
            ->with([
                'oficina',                     // Para ver en qué oficina está
                'resguardos.resguardante',     // Para ver quién lo tiene (si está asignado)
                'ubicacionActual'              // Por si está en tránsito
            ])
            // Filtramos bienes donde la oficina del bien pertenezca al mismo departamento
            ->whereHas('oficina', function ($q) use ($departamentoId) {
                $q->where('id_departamento', $departamentoId);
            });

        // --- Filtros (Buscador) ---
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('bien_descripcion', 'ILIKE', "%{$search}%")
                ->orWhere('bien_codigo', 'ILIKE', "%{$search}%");
            });
        }

        // --- Filtro por Estado ---
        if ($request->has('estado') && $request->estado) {
            $query->where('bien_estado', $request->estado);
        }

        return response()->json($query->paginate(15));
    }

}