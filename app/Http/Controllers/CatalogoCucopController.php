<?php

namespace App\Http\Controllers;

use App\Models\CatalogoCambCucop;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class CatalogoCucopController extends Controller
{
    /**
     * Busca en el catálogo de CUCOP.
     * Responde a: GET /api/catalogo-cucop
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // 1. Columnas a seleccionar
        $columns = ['id','clave_cucop', 'partida_especifica', 'descripcion', 'camb'];

        // 2. Iniciar la consulta
        $query = CatalogoCambCucop::select($columns);

        // 3. Aplicar la lógica de búsqueda (si se proporciona)
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                
                // 1. Siempre busca en la columna de texto (camb)
                $q->where('camb', '=', $searchTerm);
                //    busca también en la columna de número (clave_cucop)
                if (is_numeric($searchTerm)) {
                    $q->orWhere('clave_cucop', '=', (int)$searchTerm);
                }
            });
        }

        // 4. Obtener los resultados con paginación (más reciente -> más antiguo)
        $catalogo = $query->orderBy('id', 'desc')->paginate(15);

        return $catalogo;
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'tipo' => 'required|string|max:255',
            'clave_cucop' => 'required|integer|unique:catalogo_camb_cucop,clave_cucop', // Unique
            'partida_especifica' => 'nullable|string|max:255',
            'clave_cucop_plus' => 'nullable|string|max:255',
            'descripcion' => 'required|string',
            'nivel' => 'nullable|string|max:255',
            'camb' => 'nullable|string|max:255',
            'unidad_medida' => 'nullable|string|max:255',
            'tipo_contratacion' => 'nullable|string|max:255',
        ]);

        // Campos constantes por especificación
        $validatedData['tipo'] = '1';
        $validatedData['nivel'] = '5';
        $validatedData['unidad_medida'] = 'pieza';
        $validatedData['tipo_contratacion'] = 'adquisiciones';

        try {
            $registro = CatalogoCambCucop::create($validatedData);
            return response()->json($registro, 201);
        } catch (Throwable $e) {
            return response()->json(['error' => 'No se pudo crear el registro.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Muestra un registro específico del catálogo.
     * GET /api/catalogo-camb-cucop/{id}
     */
    public function show(CatalogoCambCucop $catalogo)
    {
        return response()->json($catalogo);
    }

    /**
     * Actualiza un registro específico del catálogo.
     * PUT/PATCH /api/catalogo-camb-cucop/{id}
     */
    public function update(Request $request, CatalogoCambCucop $catalogo)
    {
        $validatedData = $request->validate([
            'tipo' => 'required|string|max:255',
            'clave_cucop' => [
                'required',
                'integer',
                Rule::unique('catalogo_camb_cucop', 'clave_cucop')->ignore($catalogo->getKey()),
            ],
            'partida_especifica' => 'nullable|string|max:255',
            'clave_cucop_plus' => 'nullable|string|max:255',
            'descripcion' => 'required|string',
            'nivel' => 'nullable|string|max:255',
            'camb' => 'nullable|string|max:255',
            'unidad_medida' => 'nullable|string|max:255',
            'tipo_contratacion' => 'nullable|string|max:255',
        ]);

        // Forzamos los valores constantes
        $validatedData['tipo'] = '1';
        $validatedData['nivel'] = '5';
        $validatedData['unidad_medida'] = 'pieza';
        $validatedData['tipo_contratacion'] = 'adquisiciones';

        try {
            $catalogo->update($validatedData);
            return response()->json($catalogo, 200);
        } catch (Throwable $e) {
            return response()->json(['error' => 'No se pudo actualizar el registro.', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Elimina un registro específico del catálogo.
     * DELETE /api/catalogo-camb-cucop/{id}
     */
    public function destroy(CatalogoCambCucop $catalogo)
    {
        try {
            $catalogo->delete();
            return response()->json(null, 204); // 204 No Content
        } catch (Throwable $e) {
            // Esto capturará excepciones como QueryException si hay FKs asociados
            return response()->json(['error' => 'No se pudo eliminar el registro.', 'message' => $e->getMessage()], 500);
        }
    }
}