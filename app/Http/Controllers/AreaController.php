<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $areas = Area::with([
            'departamentos:id,dep_nombre',
            'edificio:id,nombre',
            'responsable:id,res_nombre,res_apellidos' // Asumiendo que esta relación existe
         ])
         ->get();

        return response()->json($areas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validación de la Regla 2: Responsable debe ser Subdirector
        $subdirectores = DB::table('resguardantes')
            ->join('usuarios', 'resguardantes.res_id_usuario', '=', 'usuarios.id')
            ->join('roles', 'usuarios.usuario_id_rol', '=', 'roles.id')
            ->where('roles.rol_nombre', 'Subdirector') // Ajusta este nombre de rol
            ->pluck('resguardantes.id');

        $datosValidados = $request->validate([
            'area_nombre' => 'required|string|max:255',
            'area_codigo' => 'required|string|unique:areas,area_codigo',
            
            // Validaciones de las llaves foráneas (nulables)
            'id_resguardante_responsable' => [
                'nullable',
                'integer',
                Rule::in($subdirectores) // ¡La regla de negocio clave!
            ],
            'id_edificio' => 'nullable|integer|exists:edificios,id',
            //'id_departamento' => 'nullable|integer|exists:departamentos,id',
        ]);

        $area = Area::create($datosValidados);

        return response()->json($area, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $area = Area::findOrFail($id);
        $area->load('departamentos:id,dep_nombre', 
                    'edificio:id,nombre', 
                    'responsable:id,res_nombre,res_apellidos');

        return response()->json($area);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Area $area)
    {
        $subdirectoresIds = DB::table('resguardantes')
            ->join('usuarios', 'resguardantes.res_id_usuario', '=', 'usuarios.id')
            ->join('roles', 'usuarios.usuario_id_rol', '=', 'roles.id')
            ->where('roles.rol_nombre', 'Subdirector')
            ->pluck('resguardantes.id');

        // 2. Validación
        $datosValidados = $request->validate([
            'area_nombre' => 'required|string|max:255',
            
            'area_codigo' => [
                'required',
                'string',
                Rule::unique('areas')->ignore($area->id) // Ignora el ID actual
            ],
            
            'id_resguardante_responsable' => [
                'nullable',
                'integer',
                Rule::in($subdirectoresIds) // Valida que el ID esté en la lista
            ],
            'id_edificio' => 'nullable|integer|exists:edificios,id',
        ]);

        // 3. Actualizamos el área
        $area->update($datosValidados);

        // 4. Devolvemos el área actualizada
        return response()->json($area);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $area = Area::findOrFail($id); // Busca el área primero
    
        // Chequeo 1: ¿Tiene departamentos asociados?
        if ($area->departamentos()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el área. Aún tiene departamentos asociados.'
            ], 409); // 409 Conflict
        }
        
        // Chequeo 2: ¿Tiene bienes asociados (indirectamente)?
        $departamentoIds = $area->departamentos()->pluck('id');
        $bienesAsociados = Bien::whereIn('bien_id_dep', $departamentoIds)->count();
    
        if ($bienesAsociados > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el área. Sus departamentos aún tienen ' . $bienesAsociados . ' bienes asociados.'
            ], 409);
        }
    
        // Si todo está limpio, se elimina
        $area->delete();
        return response()->noContent();
    }

    /**
     * Obtiene la estructura jerárquica (Departamentos y Oficinas)
     * de un Área específica.
     * * Responde a: GET /api/areas/{area}/structure
     */
    public function getStructure(Area $area)
    {
        // 1. Inicia la consulta en los departamentos de ESTA área
        $structure = $area->departamentos()
            ->with([
                // 2. Carga la relación 'oficinas' para CADA departamento
                'oficinas' => function ($query) {
                    // 3. Selecciona solo las columnas que necesitas de las oficinas
                    $query->select('id', 'nombre', 'ofi_codigo', 'id_departamento');
                }
            ])
            // 4. Selecciona solo las columnas que necesitas de los departamentos
            ->select('id', 'dep_nombre', 'dep_codigo', 'id_area')
            ->get();

        // 5. Devuelve el JSON
        return response()->json($structure);
    }
}
