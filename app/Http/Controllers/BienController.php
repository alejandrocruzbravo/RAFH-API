<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Oficina;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Throwable;

class BienController extends Controller
{
    /**
     * Muestra una lista paginada de bienes.
     * Esta es la vista principal (Nivel 4 de tu propuesta).
     */
    public function index(Request $request)
    {
        // Valida que el frontend esté pidiendo un filtro
        $request->validate([
            // Para la búsqueda global (aguja en el pajar)
            'search' => 'nullable|string|min:3',
            // Para la navegación jerárquica
            'id_oficina' => 'nullable|integer|exists:oficinas,id'
        ]);

        $query = Bien::with([
            // Cargamos la ubicación: Oficina -> Departamento -> Área
            'oficina:id,nombre,id_departamento,id_edificio',
            'oficina.departamento:id,dep_nombre,id_area',
            'oficina.departamento.area:id,area_nombre',
            // Cargamos el último resguardo para saber quién lo tiene
            'resguardos' => function ($q) {
                $q->latest('resguardo_fecha_asignacion')->limit(1)->with('resguardante:id,res_nombre,res_apellidos');
            }
        ]);

        // --- LÓGICA DE BÚSQUEDA ---

        // 1. Búsqueda Global (Si el usuario usó la barra de búsqueda)
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function($q) use ($term) {
                $q->where('bien_codigo', 'like', "%{$term}%")
                  ->orWhere('bien_serie', 'like', "%{$term}%")
                  ->orWhere('bien_descripcion', 'like', "%{$term}%")
                  ->orWhere('bien_clave', 'like', "%{$term}%");
            });
        } 
        // 2. Búsqueda Jerárquica (Si el usuario navegó a una oficina)
        else if ($request->filled('id_oficina')) {
            $query->where('id_oficina', $request->input('id_oficina'));
        } 
        // 3. Si no hay filtro, devuelve todo (no recomendado con 20k registros)
        // (Podríamos forzar un error si no hay filtro)
        else {
            // Opcional: devolver un error si no se filtra
             return response()->json(['error' => 'Se requiere un filtro de búsqueda o de oficina.'], 400);
        }

        // Seleccionamos solo las columnas necesarias para la tabla "rápida"
        $bienes = $query->select(
                'id', 'bien_codigo', 'bien_descripcion', 'bien_serie', 
                'bien_marca', 'bien_modelo', 'bien_estado', 'id_oficina'
            )
            ->orderBy('id', 'desc')
            ->paginate(25); // Paginación de 25 (más reciente -> más antiguo)

        return $bienes;
    }


    /**
     * Almacena uno o más bienes (creación por lotes)
     * y genera sus códigos automáticamente.
     */
    public function store(Request $request)
    {
        // 1. Validar los datos de entrada
        // (Añadimos 'cantidad' y ajustamos reglas según tu JSON)
        $validatedData = $request->validate([
            'id_oficina' => 'nullable|integer|exists:oficinas,id',
            'bien_modelo' => 'nullable|string|max:255',
            'bien_serie' => 'nullable|string|max:255', // Ya no es 'unique'
            'bien_descripcion' => 'required|string',
            'bien_tipo_adquisicion' => 'nullable|string|max:255',
            'bien_fecha_alta' => 'nullable|date',
            'bien_valor_monetario' => 'required|numeric|min:0',
            'bien_provedor' => 'nullable|string|max:255',
            'bien_numero_factura' => 'nullable|string|max:255',
            'bien_estado' => 'nullable|string|max:255',
            'bien_marca' => 'nullable|string|max:255',
            
            // --- Campos clave para la generación ---
            'bien_clave' => 'required|string|max:255', // El CAMB
            'bien_y' => 'required|string|max:4',       // El Año
            'cantidad' => 'required|integer|min:1',  // ¡La cantidad de lotes!
        ]);

        $bienesCreados = [];
        $claveCamb = $validatedData['bien_clave'];
        $anio = $validatedData['bien_y'];
        $cantidad = (int)$validatedData['cantidad'];

        // Prepara el array de datos base (todo menos los campos generados)
        $baseData = $validatedData;
        unset($baseData['cantidad']); // No queremos guardar 'cantidad' en la BD

        try {
            // 2. Iniciar UNA SOLA transacción para TODO el lote
            DB::beginTransaction();

            // 3. Obtener el número de secuencia INICIAL
            // Bloqueamos la fila para evitar que otro proceso tome la misma secuencia
            $ultimoBien = Bien::where('bien_clave', $claveCamb)
                                ->orderByRaw('CAST(bien_secuencia AS INTEGER) DESC')
                                ->lockForUpdate()
                                ->first();

            $siguienteSecuenciaNum = $ultimoBien ? (int)$ultimoBien->bien_secuencia + 1 : 1;
            
            // 4. Componentes del código que no cambian
            $componente_anio = substr($anio, -2); // "2025" -> "25"
            $componente_instituto = '23';

            // 5. Bucle de Creación
            for ($i = 0; $i < $cantidad; $i++) {
                
                $secuenciaActualNum = $siguienteSecuenciaNum + $i;
                $componente_secuencia_str = str_pad($secuenciaActualNum, 5, '0', STR_PAD_LEFT);

                // 6. Ensamblar el código único
                $nuevoCodigo = "{$claveCamb}-{$componente_anio}-{$componente_instituto}-{$componente_secuencia_str}";

                // 7. Preparar datos para este bien específico
                $dataParaEsteBien = $baseData;
                $dataParaEsteBien['bien_codigo'] = $nuevoCodigo;
                $dataParaEsteBien['bien_secuencia'] = (string)$secuenciaActualNum;
                
                // 8. Asignar 'SIN SERIE' si no se proporciona
                $dataParaEsteBien['bien_serie'] = $validatedData['bien_serie'] ?? 'SIN SERIE';

                // 9. Crear el bien
                $bien = Bien::create($dataParaEsteBien);
                $bienesCreados[] = $bien;
            }

            // 10. Confirmar la transacción
            DB::commit();

            // 11. Devolver el array de bienes creados
            return response()->json($bienesCreados, 201);

        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al crear el lote de bienes. La operación fue revertida.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Muestra la "Ficha Técnica" completa de un bien.
     */
    public function show(Bien $biene)
    {
        // Carga todo: ubicación, historial de resguardos, 
        // historial de movimientos e historial de traspasos
        $biene->load(
            'oficina.departamento.area', 
            'oficina.edificio', 
            'resguardos.resguardante', 
            'movimientosBien', 
            'traspasos.usuarioOrigen'
        );
        return $biene;
    }

    /**
     * Actualiza un bien específico.
     */
    public function update(Request $request, Bien $biene)
    {
        $validatedData = $request->validate([
            'bien_modelo' => 'nullable|string|max:255',
            'bien_descripcion' => 'required|string',
            'bien_tipo_adquisicion' => 'nullable|string|max:255',
            'bien_valor_monetario' => 'nullable|numeric|min:0',
            'bien_provedor' => 'nullable|string|max:255',
            'bien_numero_factura' => 'nullable|string|max:255',
            'bien_estado' => 'nullable|string|max:255',
            'bien_marca' => 'nullable|string|max:255',
            
            // Reglas 'unique' con 'ignore'
            'bien_codigo' => 'required|string|'.Rule::unique('bienes')->ignore($biene->id),
            'bien_serie' => 'nullable|string|max:255|',
        ]);

        $biene->update($validatedData);
        return response()->json($biene, 200);
    }

    /**
     * Elimina un bien (Baja de inventario).
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
            return response()->json(['error' => 'No se puede eliminar, tiene registros asociados.'], 409);
        } catch (Throwable $e) {
            return response()->json(['error' => 'Error al eliminar el bien.', 'message' => $e->getMessage()], 500);
        }
    }

    public function darDeBaja(Bien $bien)
    {
        try {
            $bien->bien_estado = 'Baja';
            $bien->save();
            
            // Devuelve el bien actualizado con su nuevo estado
            return response()->json($bien, 200);

        } catch (Throwable $e) {
            return response()->json([
                'error' => 'No se pudo actualizar el estado del bien.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mueve un bien a una nueva oficina.
     * Responde a: PUT /api/bienes/{bien}/mover
     */
    public function mover(Request $request, Bien $bien)
    {
        // 1. Validar el ID de la nueva oficina enviado en el body
        $validatedData = $request->validate([
            'id_oficina_nueva' => 'required|integer|exists:oficinas,id'
        ]);

        try {
            $nuevaOficinaId = $validatedData['id_oficina_nueva'];

            // 2. Buscar la oficina para obtener su nombre
            $nuevaOficina = Oficina::findOrFail($nuevaOficinaId);

            // 3. Actualizar el bien
            $bien->id_oficina = $nuevaOficina->id;
            
            $bien->save();
            
            // 5. Devolver el bien actualizado con la nueva oficina cargada
            return response()->json($bien->load('oficina'), 200);

        } catch (Throwable $e) {
            return response()->json([
                'error' => 'No se pudo mover el bien.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Compara los bienes escaneados contra lo que dice el sistema.
     * NO guarda un historial en base de datos.
     */
   public function compararInventario(Request $request)
    {
        // ... (validación e inputs igual que antes) ...

        $idOficina = $request->input('id_oficina');
        $escaneados = collect($request->input('claves_escaneadas'));

        // 1. Obtener teóricos
        $bienesTeoricos = Bien::where('id_oficina', $idOficina)->get();
        $clavesTeoricas = $bienesTeoricos->pluck('bien_codigo');

        // 2. Calcular ENCONTRADOS
        $encontrados = $bienesTeoricos->whereIn('bien_codigo', $escaneados);

        // 3. Calcular FALTANTES
        $faltantes = $bienesTeoricos->whereNotIn('bien_codigo', $escaneados)->values();

        // 4. Calcular SOBRANTES
        $clavesSobrantes = $escaneados->diff($clavesTeoricas);
        $sobrantesInfo = Bien::whereIn('bien_codigo', $clavesSobrantes)
                             ->with('oficina')
                             ->get();

        return response()->json([
            'resumen' => [
                'total_esperados' => $bienesTeoricos->count(),
                'total_escaneados' => $escaneados->count(),
                'conteo_encontrados' => $encontrados->count(), // Agregamos conteo
                'conteo_faltantes' => $faltantes->count(),
                'conteo_sobrantes' => $sobrantesInfo->count(),
            ],
            // --- ¡AQUÍ ESTÁ EL CAMBIO! ---
            // Añadimos la lista completa de objetos encontrados.
            // Usamos ->values() para reiniciar los índices del array (0, 1, 2...)
            'encontrados' => $encontrados->values(), 
            'faltantes' => $faltantes,
            'sobrantes' => $sobrantesInfo,
        ]);
    }
}