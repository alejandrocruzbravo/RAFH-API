<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class DepartamentoController extends Controller
{
    /**
     * Muestra una lista de los departamentos.
     * GET /api/departamentos
     */
    public function index()
    {
        // Incluimos la relación 'area' para saber su nombre
        $departamentos = Departamento::with('area')->latest()->paginate(10);
        return $departamentos;
    }

    /**
     * Almacena un nuevo departamento en la base de datos.
     * POST /api/departamentos
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'dep_nombre' => 'required|string|max:255',
            'dep_codigo' => 'required|string|max:255|unique:departamentos,dep_codigo',
            'dep_resposable' => 'nullable|string|max:255',
            'dep_correo_institucional' => 'nullable|email|max:255',
            'id_area' => 'required|exists:areas,id',
        ]);
        $departamento = Departamento::create($validatedData);
        return response()->json($departamento->load('area'), 201);
    }

    /**
     * Muestra un departamento específico.
     * GET /api/departamentos/{id}
     */
    public function show(Departamento $departamento)
    {
        // Carga la relación 'area' y devuelve el JSON
        return $departamento->load('area');
    }

    /**
     * Actualiza un departamento específico.
     * PUT /api/departamentos/{id}
     */
    public function update(Request $request, Departamento $departamento)
    {
        $validatedData = $request->validate([
            'dep_nombre' => 'required|string|max:255',
            'dep_codigo' => 'required|string|max:255|unique:departamentos,dep_codigo,' . $departamento->id,
            'dep_resposable' => 'nullable|string|max:255',
            'dep_correo_institucional' => 'nullable|email|max:255',
            'id_area' => 'required|exists:areas,id',
        ]);

        $departamento->update($validatedData);

        return response()->json($departamento->load('area'), 200);
    }

    /**
     * Elimina un departamento.
     * DELETE /api/departamentos/{id}
     */
    public function destroy(Departamento $departamento)
    {
        try {
            $departamento->delete();
            return response()->json(null, 204); // Éxito, sin contenido

        } catch (QueryException $e) {
            // Manejar error de llave foránea (si el depto tiene resguardantes)
            $errorCode = $e->errorInfo[1];
            if ($errorCode == 1451 || str_contains($e->getMessage(), 'foreign key constraint fails')) {
                return response()->json([
                    'error' => 'No se puede eliminar el departamento porque tiene resguardantes u otros registros asociados.'
                ], 409); // 409 Conflicto
            }
            
            // Otro error de base de datos
            return response()->json(['error' => 'Error al eliminar el recurso.'], 500);
        }
    }
}