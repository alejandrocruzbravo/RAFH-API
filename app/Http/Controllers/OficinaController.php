<?php

namespace App\Http\Controllers;

use App\Models\Oficina;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class OficinaController extends Controller
{
    /**
     * Muestra una lista de las oficinas.
     * GET /oficinas
     */
    public function index(Request $request)
    {
        $query = Oficina::with('edificio'); // Carga la relación 'edificio'

        // Búsqueda por nombre o referencia
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('nombre', 'like', "%{$searchTerm}%")
                  ->orWhere('referencia', 'like', "%{$searchTerm}%");
            });
        }

        $oficinas = $query->latest()->paginate(10);
        
        return $oficinas;
    }

    /**
     * Almacena una nueva oficina en la base de datos.
     * POST /oficinas
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id_departamento' => 'required|integer|exists:departamentos,id',
            'id_edificio' => 'required|exists:edificios,id',
            'ofi_codigo' => 'required|string|max:255|unique:oficinas,ofi_codigo',
            'nombre' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:255',
        ]);

        $oficina = Oficina::create($validatedData);

        // Devolvemos la oficina creada, cargando la relación 'edificio'
        return response()->json($oficina->load('departamento','edificio'), 201);
    }

    /**
     * Muestra una oficina específica.
     * GET /oficinas/{id}
     */
    public function show(Oficina $oficina)
    {
        // Carga la relación 'edificio' y la devuelve
        return $oficina->load('edificio');
    }

    /**
     * Actualiza una oficina específica.
     * PUT /oficinas/{id}
     */
    public function update(Request $request, Oficina $oficina)
    {
        $validatedData = $request->validate([
            'id_departamento' => 'required|integer|exists:departamentos,id',
            'id_edificio' => 'required|exists:edificios,id',
            'ofi_codigo' => [
                'required',
                'string',
                'max:255',
                Rule::unique('oficinas')->ignore($oficina->id),
            ],
            'nombre' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:255',
        ]);

        $oficina->update($validatedData);

        return response()->json($oficina->load('departamento','edificio'), 200);
    }

    /**
     * Elimina una oficina.
     * DELETE /oficinas/{id}
     */
    public function destroy(Oficina $oficina)
    {
        // 1. Chequeo: ¿Tiene bienes asociados?
        // Esto asume que tu modelo 'Oficina' tiene la relación 'bienes()'
        if ($oficina->bienes()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar la oficina. Aún tiene bienes asociados.'
            ], 409); // 409 Conflict
        }
        // 3. Si todo está limpio, se elimina
        $oficina->delete();
        
        return response()->json(null, 204); // 204 No Content
    }

    /**
     * Muestra los bienes asignados a una oficina específica.
     * Responde a: GET /oficinas/{oficina}/bienes
     */
    public function getBienes(Request $request, Oficina $oficina)
    {
        // 1. Inicia la consulta en los 'bienes' de ESTA oficina
        $query = $oficina->bienes()->with([
            // Cargamos el último resguardo para saber quién lo tiene
            'resguardos' => function ($q) {
                $q->latest('resguardo_fecha_asignacion')->limit(1)->with('resguardante:id,res_nombre,res_apellidos');
            }
        ]);

        // 2. (Opcional) Filtrar por estado DENTRO de la oficina
        if ($request->filled('estado')) {
            $query->where('bien_estado', $request->input('estado'));
        }

        // 3. (Opcional) Buscar DENTRO de la oficina
        if ($request->filled('search')) {
            $term = $request->input('search');
            $query->where(function($q) use ($term) {
                $q->where('bien_codigo', 'like', "%{$term}%")
                  ->orWhere('bien_serie', 'like', "%{$term}%")
                  ->orWhere('bien_descripcion', 'like', "%{$term}%");
            });
        }

        // 4. Selecciona las columnas necesarias para la tabla y pagina
        $bienes = $query->select(
                'id', 'bien_codigo', 'bien_descripcion', 'bien_serie', 'bien_caracteristicas',
                'bien_marca', 'bien_modelo', 'bien_estado', 'id_oficina', 'bien_provedor', 'bien_tipo_adquisicion', 'bien_numero_factura', 'bien_valor_monetario'
            )
            ->orderBy('id', 'desc')
            ->paginate(15); // Paginación (más reciente -> más antiguo)

        return $bienes;
    }
}