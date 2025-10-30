<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Edificio;
use App\Models\Resguardante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AreaFormController extends Controller
{
    /**
     * Devuelve los datos necesarios para los
     * menús desplegables del formulario de Áreas.
     */
    public function getOptions()
    {
        // 1. Obtener la lista de Jefes (tu regla de negocio)
        $subdirectores = DB::table('resguardantes')
        ->join('usuarios', 'resguardantes.res_id_usuario', '=', 'usuarios.id')
        ->join('roles', 'usuarios.usuario_id_rol', '=', 'roles.id')
        ->where('roles.rol_nombre', 'Subdirector') 
        ->select('resguardantes.id', 'resguardantes.res_nombre') 
        ->get();
        // 2. Obtener todos los edificios
        $edificios = Edificio::select('id', 'nombre')->get();

        // 3. Obtener todos los departamentos
        $departamentos = Departamento::select('id', 'dep_nombre')->get();

        // 4. Devolver todo en una sola respuesta JSON
        return response()->json([
            'responsables' => $subdirectores,
            'edificios' => $edificios,
            'departamentos' => $departamentos,
        ]);
    }
}