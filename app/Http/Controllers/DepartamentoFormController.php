<?php

namespace App\Http\Controllers;

use App\Models\Area; // Importamos el modelo Area
use Illuminate\Http\Request;

class DepartamentoFormController extends Controller
{
    /**
     * Maneja la única acción del controlador.
     * Obtiene todos los datos necesarios para los formularios
     * de creación y edición de Departamentos.
     */
    public function __invoke(Request $request)
    {
        // 1. Obtenemos la lista de áreas (solo ID y nombre)
        $areas = Area::select('id', 'area_nombre')
                     ->orderBy('area_nombre')
                     ->get();

        // 2. (En el futuro) Podrías agregar más datos aquí:
        // $edificios = Edificio::select('id', 'nombre')->get();
        
        // 3. Devolvemos un objeto JSON con todas las listas
        return response()->json([
            'areas' => $areas,
            // 'edificios' => $edificios, 
        ]);
    }
}