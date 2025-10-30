<?php

namespace App\Http\Controllers;

use App\Models\Rol; // <-- Importa el modelo Rol
use Illuminate\Http\Request;

class RolFormController extends Controller
{
    /**
     * Obtiene la lista de todos los roles para llenar
     * los dropdowns en los formularios.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        // Optimizamos la consulta para obtener solo el ID y el nombre
        $roles = Rol::select('id', 'rol_nombre')
                    ->orderBy('rol_nombre') // Ordena alfabéticamente
                    ->get();
        
        // Devolvemos la colección como un array JSON
        return response()->json($roles);
    }
}