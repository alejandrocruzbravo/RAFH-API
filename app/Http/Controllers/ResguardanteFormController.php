<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Departamento;
use App\Models\Rol;
use App\Models\Oficina;
use Illuminate\Support\Facades\DB;

class ResguardanteFormController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $departamentos = Departamento::select('id', 'dep_nombre')
                                     ->orderBy('dep_nombre')
                                     ->get();
        
        // 2. Obtener los roles con ID > 3
        $roles = Rol::select('id', 'rol_nombre')
                    ->where('id', '>=', 3)
                    ->orderBy('rol_nombre')
                    ->get();

        // --- AÑADE ESTA CONSULTA ---
        $oficinas = Oficina::select('id', 'nombre') // Asumiendo que la columna es 'nombre'
                           ->with('edificio:id,nombre') // Carga Edificio (solo ID y nombre)
                           ->orderBy('nombre')
                           ->get();

        return response()->json([
            'departamentos' => $departamentos,
            'roles' => $roles,
            'oficinas' => $oficinas,
        ]);
    }
}
