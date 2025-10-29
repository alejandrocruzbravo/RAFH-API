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
            'responsable:id,res_nombre,res_apellido1' // Asumiendo que esta relación existe
         ])
         ->get();

return response()->json($areas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validación de la Regla 2: Responsable debe ser Jefe
        $jefesDeDepartamentoIds = DB::table('resguardantes')
            ->join('usuarios', 'resguardantes.res_id_usuario', '=', 'usuarios.id')
            ->join('roles', 'usuarios.usuario_id_rol', '=', 'roles.id')
            ->where('roles.rol_nombre', 'Jefe de Departamento') // Ajusta este nombre de rol
            ->pluck('resguardantes.id');

        $datosValidados = $request->validate([
            'area_nombre' => 'required|string|max:255',
            'area_codigo' => 'required|string|unique:areas,area_codigo',
            
            // Validaciones de las llaves foráneas (nulables)
            'id_resguardante_responsable' => [
                'nullable',
                'integer',
                Rule::in($jefesDeDepartamentoIds) // ¡La regla de negocio clave!
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
                    'responsable:id,res_nombre,res_apellido1');

        return response()->json($area);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $area = Area::findOrFail($id); // Busca el área primero
        // 1. Buscamos la lista de Jefes
        $jefesDeDepartamentoIds = DB::table('resguardantes')
            ->join('usuarios', 'resguardantes.res_id_usuario', '=', 'usuarios.id')
            ->join('roles', 'usuarios.usuario_id_rol', '=', 'roles.id')
            ->where('roles.rol_nombre', 'Jefe de Departamento')
            ->pluck('resguardantes.id');

        // 2. Validación (¡La regla 'unique' es diferente!)
        $datosValidados = $request->validate([
            'area_nombre' => 'required|string|max:255',
            
            // Le decimos a Laravel que ignore el ID del área actual
            // al comprobar si el 'area_codigo' es único.
            'area_codigo' => [
                'required',
                'string',
                Rule::unique('areas')->ignore($area->id) 
            ],
            
            'id_resguardante_responsable' => [
                'nullable',
                'integer',
                Rule::in($jefesDeDepartamentoIds)
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
}
