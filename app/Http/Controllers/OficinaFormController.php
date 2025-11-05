<?php

namespace App\Http\Controllers;

use App\Models\Edificio; // Importamos Edificio
use Illuminate\Http\Request;

class OficinaFormController extends Controller
{
    /**
     * Obtiene todos los datos necesarios para los formularios
     * de creación y edición de Oficinas.
     */
    public function __invoke(Request $request)
    {
        // 1. Obtenemos la lista de edificios (solo ID y nombre)
        $edificios = Edificio::select('id', 'nombre')
                             ->orderBy('nombre')
                             ->get();

        // 2. Devolvemos un objeto JSON con todas las listas
        return response()->json([
            'edificios' => $edificios,
        ]);
    }
}