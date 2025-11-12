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
     * GET /api/oficinas
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
     * POST /api/oficinas
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
     * GET /api/oficinas/{id}
     */
    public function show(Oficina $oficina)
    {
        // Carga la relación 'edificio' y la devuelve
        return $oficina->load('edificio');
    }

    /**
     * Actualiza una oficina específica.
     * PUT /api/oficinas/{id}
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
     * DELETE /api/oficinas/{id}
     */
    public function destroy(Oficina $oficina)
    {
        try {
            $oficina->delete();
            return response()->json(null, 204); // Éxito, sin contenido

        } catch (QueryException $e) {
            // Manejar error si la oficina tiene bienes u otros registros asociados
            return response()->json([
                'error' => 'No se puede eliminar la oficina porque tiene registros asociados (como bienes, etc.).'
            ], 409); // 409 Conflicto
        }
    }
}