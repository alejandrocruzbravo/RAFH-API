<?php

namespace App\Http\Controllers;

use App\Models\Edificio;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class EdificioController extends Controller
{
    /**
     * Muestra una lista de los edificios.
     * GET /api/edificios
     */
    public function index()
    {
        // Para una lista simple, no paginamos.
        // Si esperas tener CIENTOS de edificios, cambia get() por paginate(15).
        $edificios = Edificio::orderBy('nombre')->paginate(10);
        return $edificios;
    }

    /**
     * Almacena un nuevo edificio en la base de datos.
     * POST /api/edificios
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255|unique:edificios,nombre',
        ]);

        $edificio = Edificio::create($validatedData);

        return response()->json($edificio, 201);
    }

    /**
     * Muestra un edificio específico.
     * GET /api/edificios/{id}
     */
    public function show(Edificio $edificio)
    {
        // Cargamos las áreas que pertenecen a este edificio
        return $edificio->load('areas');
    }

    /**
     * Actualiza un edificio específico.
     * PUT /api/edificios/{id}
     */
    public function update(Request $request, Edificio $edificio)
    {
        $validatedData = $request->validate([
            // Se asegura que el nombre sea único, ignorando su propio ID
            'nombre' => 'required|string|max:255|unique:edificios,nombre,' . $edificio->id,
        ]);

        $edificio->update($validatedData);

        return response()->json($edificio, 200);
    }

    /**
     * Elimina un edificio.
     * DELETE /api/edificios/{id}
     */
    public function destroy(Edificio $edificio)
    {
        try {
            $edificio->delete();
            return response()->json(null, 204); // Éxito, sin contenido

        } catch (QueryException $e) {
            // Manejar error de llave foránea (si el edificio tiene áreas)
            return response()->json([
                'message' => 'No se puede eliminar el edificio porque tiene oficinas asociadas.'
            ], 409); // 409 Conflicto
        }
    }
}