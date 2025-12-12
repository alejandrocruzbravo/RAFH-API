<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA; 
/**
 * @OA\Schema(
 * schema="Traspaso",
 * title="Traspaso (Solicitud)",
 * description="Solicitud de transferencia de un bien entre dos resguardantes.",
 * required={"traspaso_id_bien", "traspaso_id_usuario_origen", "traspaso_id_usuario_destino", "traspaso_fecha_solicitud"},
 * @OA\Property(property="id", type="integer", example=150, description="ID único de la solicitud"),
 * @OA\Property(property="traspaso_id_bien", type="integer", description="ID del bien a transferir", example=55),
 * @OA\Property(property="traspaso_id_usuario_origen", type="integer", description="ID del Resguardante (Empleado) que entrega", example=10),
 * @OA\Property(property="traspaso_id_usuario_destino", type="integer", description="ID del Resguardante (Empleado) que recibe", example=12),
 * @OA\Property(property="traspaso_fecha_solicitud", type="string", format="date", description="Fecha en que se creó la solicitud", example="2025-10-25"),
 * @OA\Property(property="traspaso_estado", type="string", description="Estado actual del flujo", example="Pendiente", enum={"Pendiente", "Aprobada", "Rechazada"}),
 * @OA\Property(property="traspaso_observaciones", type="string", nullable=true, description="Notas opcionales", example="El equipo se entrega sin cargador"),
 * @OA\Property(property="created_at", type="string", format="date-time"),
 * @OA\Property(property="updated_at", type="string", format="date-time"),
 * 
 * @OA\Property(
 * property="bien",
 * ref="#/components/schemas/Bien",
 * description="Datos del bien involucrado (si se carga relación)"
 * ),
 * @OA\Property(
 * property="resguardanteOrigen",
 * ref="#/components/schemas/Resguardante",
 * description="Datos del empleado emisor (si se carga relación)"
 * ),
 * @OA\Property(
 * property="resguardanteDestino",
 * ref="#/components/schemas/Resguardante",
 * description="Datos del empleado receptor (si se carga relación)"
 * )
 * )
 */
class Traspaso extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'traspasos';

    /**
     * La llave primaria asociada con la tabla.
     */
    protected $primaryKey = 'id';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'traspaso_id_bien',
        'traspaso_id_usuario_origen',
        'traspaso_id_usuario_destino',
        'traspaso_fecha_solicitud',
        'traspaso_estado',
        'traspaso_observaciones',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'traspaso_fecha_solicitud' => 'date',
    ];
    /**
     * Obtiene el bien que está siendo traspasado.
     */
    public function bien()
    {
        // Un traspaso pertenece a un Bien
        return $this->belongsTo(Bien::class, 'traspaso_id_bien', 'id');
    }

    public function resguardanteOrigen() // Sugiero renombrar la relación si puedes
    {
        // 'traspaso_id_usuario_origen' ahora guarda un ID de resguardante
         return $this->belongsTo(Resguardante::class, 'traspaso_id_usuario_origen');
    }

    public function resguardanteDestino()
    {
        // 'traspaso_id_usuario_destino' ahora guarda un ID de resguardante
         return $this->belongsTo(Resguardante::class, 'traspaso_id_usuario_destino');
    }
}