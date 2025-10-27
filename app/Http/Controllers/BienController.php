<?php

namespace App\Http\Controllers;

use App\Models\Bien; // <-- Importa el modelo
use Illuminate\Http\Request;

class BienController extends Controller
{
    /**
     * Muestra una lista de los bienes.
     */
    public function index()
    {
        // Devolvemos todos los bienes, ordenados por el más reciente
        $bienes = Bien::orderBy('created_at', 'desc')->get();
        
        return response()->json($bienes);
    }

    /**
     * Almacena un nuevo bien.
     */
    public function store(Request $request)
    {
        // 1. Validamos los datos de entrada
        $datosValidados = $request->validate([
            // Validamos que el código sea único en la tabla 'bienes'
            'bien_codigo' => 'required|string|unique:bienes,bien_codigo', 
            'bien_nombre' => 'required|string|max:255',
            'bien_categoria' => 'nullable|string',
            'bien_ubicacion_actual' => 'nullable|string',
            'bien_estado' => 'nullable|string',
            'bien_modelo' => 'nullable|string',
            'bien_marca' => 'nullable|string',
            'bien_fecha_adquision' => 'nullable|date',
            'bien_valor_monetario' => 'required|numeric|min:0',
            
            // Validamos que el departamento exista en la tabla 'departamentos'
            'bien_id_dep' => 'required|integer|exists:departamentos,id', 
        ]);

        // 2. Creamos el bien si la validación pasa
        $bien = Bien::create($datosValidados);

        // 3. Devolvemos el bien creado con un código 201
        return response()->json($bien, 201);
    }

    /**
     * Muestra un bien específico.
     * (Lo implementaremos después)
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Actualiza un bien específico.
     * (Lo implementaremos después)
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Elimina un bien específico.
     * (Lo implementaremos después)
     */
    public function destroy(string $id)
    {
        //
    }
}