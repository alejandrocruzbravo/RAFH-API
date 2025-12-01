<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionInventario;
use Illuminate\Http\Request;

class ConfiguracionInventarioController extends Controller
{
    // GET: Obtener la configuración actual
    public function show(Request $request)
    {
        // Para el demo, asumimos Institución ID = 1
        $config = ConfiguracionInventario::where('institucion_id', 1)->first();

        if (!$config) {
            // Si no existe configuración previa, devolvemos null o un objeto vacío
            return response()->json(null);
        }

        // Devolvemos DIRECTAMENTE el JSON guardado
        return response()->json($config->configuracion_json);
    }

    // POST: Guardar o Actualizar configuración
    public function store(Request $request)
    {
        // Recibimos todo el JSON que manda Vue ({ structure:..., prefixes:... })
        $jsonData = $request->all();

        // Usamos updateOrCreate para no duplicar filas
        $config = ConfiguracionInventario::updateOrCreate(
            ['institucion_id' => 1], // Buscamos por ID 1
            ['configuracion_json' => $jsonData] // Actualizamos el JSON
        );

        return response()->json([
            'message' => 'Configuración guardada correctamente',
            'data' => $config->configuracion_json
        ]);
    }
}