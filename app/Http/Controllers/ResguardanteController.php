<?php

namespace App\Http\Controllers;

use App\Models\Resguardante;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException; // Para la excepción

class ResguardanteController extends Controller
{
    /**
     * Muestra la lista de resguardantes.
     * Método: GET
     * URL: /api/resguardantes
     */
    public function index(Request $request)
    {
        $query = Resguardante::with('departamento.area');

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('res_nombre', 'like', "%{$searchTerm}%")
                  ->orWhere('res_apellido1', 'like', "%{$searchTerm}%")
                  ->orWhere('res_correo', 'like', "%{$searchTerm}%");
            });
        }

        $resguardantes = $query->latest()->paginate(10);

        // Laravel convierte automáticamente la paginación a JSON
        return $resguardantes;
    }

    /**
     * Almacena un nuevo resguardante.
     * Método: POST
     * URL: /api/resguardantes
     */
    public function store(Request $request)
    {
        // La validación en una API, si falla,
        // automáticamente devuelve un JSON con error 422.
        $validatedData = $request->validate([
            'res_nombre' => 'required|string|max:255',
            'res_apellido1' => 'required|string|max:255',
            'res_apellido2' => 'nullable|string|max:255',
            'res_puesto' => 'required|string|max:255',
            'res_correo' => 'required|email|unique:resguardantes,res_correo',
            'res_telefono' => 'nullable|string|max:20',
            'res_departamento' => 'required|exists:departamentos,id',
            'res_id_usuario' => 'nullable|exists:usuarios,id|unique:resguardantes,res_id_usuario',
        ]);

        $resguardante = Resguardante::create($validatedData);

        // Devolvemos el nuevo recurso creado con un código 201
        return response()->json($resguardante, 201);
    }

    /**
     * Muestra un resguardante específico.
     * Método: GET
     * URL: /api/resguardantes/{id}
     */
    public function show(Resguardante $resguardante)
    {
        // Cargamos las relaciones y devolvemos el resguardante
        return $resguardante->load('departamento.area', 'usuario');
    }

    /**
     * Actualiza un resguardante.
     * Método: PUT/PATCH
     * URL: /api/resguardantes/{id}
     */
    public function update(Request $request, Resguardante $resguardante)
    {
        $validatedData = $request->validate([
            'res_nombre' => 'required|string|max:255',
            'res_apellido1' => 'required|string|max:255',
            'res_apellido2' => 'nullable|string|max:255',
            'res_puesto' => 'required|string|max:255',
            'res_correo' => 'required|email|unique:resguardantes,res_correo,' . $resguardante->id,
            'res_telefono' => 'nullable|string|max:20',
            'res_departamento' => 'required|exists:departamentos,id',
            'res_id_usuario' => 'nullable|exists:usuarios,id|unique:resguardantes,res_id_usuario,' . $resguardante->id,
        ]);

        $resguardante->update($validatedData);

        // Devolvemos el recurso actualizado
        return response()->json($resguardante, 200);
    }

    /**
     * Elimina un resguardante.
     * Método: DELETE
     * URL: /api/resguardantes/{id}
     */
    public function destroy(Resguardante $resguardante)
    {
        try {
            $resguardante->delete();
            // Devolvemos una respuesta vacía con código 204 (Sin Contenido)
            return response()->json(null, 204);

        } catch (QueryException $e) {
            // Manejar error de llave foránea (si tiene bienes)
            // Código 409 (Conflicto)
            return response()->json([
                'error' => 'No se puede eliminar el resguardante porque tiene bienes asignados.'
            ], 409);
        }
    }

    /**
     * Genera un reporte.
     * Método: GET
     * URL: /api/resguardantes-reporte
     */
    public function reporte(Request $request)
    {
        // La lógica del reporte cambiaría.
        // Quizás genera el archivo y devuelve un enlace para descargarlo.
        // Por ahora, solo devolvemos información.
        return response()->json([
            'info' => 'Función de reporte aún no implementada.'
        ], 501); // 501 Not Implemented
    }
}