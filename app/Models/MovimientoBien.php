<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA; // Importante

/**
 * @OA\Schema(
 * schema="MovimientoBien",
 * title="Movimiento de Bien",
 * description="Registro histórico de una acción realizada sobre un bien (Alta, Baja, Traslado, etc.).",
 * required={"movimiento_id_bien", "movimiento_fecha", "movimiento_tipo", "movimiento_cantidad"},
 * @OA\Property(property="id", type="integer", example=1050, description="ID único del movimiento"),
 * @OA\Property(property="movimiento_id_bien", type="integer", description="ID del bien afectado", example=55),
 * @OA\Property(property="movimiento_id_dep", type="integer", nullable=true, description="ID del departamento destino (ubicación física)", example=3),
 * @OA\Property(property="movimiento_fecha", type="string", format="date-time", description="Fecha y hora del suceso", example="2024-11-20T14:30:00Z"),
 * @OA\Property(property="movimiento_tipo", type="string", description="Tipo de operación", example="MOVIMIENTO", enum={"ALTA", "BAJA", "MOVIMIENTO", "EN_TRANSITO"}),
 * @OA\Property(property="movimiento_cantidad", type="integer", description="Cantidad de bienes afectados (generalmente 1)", example=1),
 * @OA\Property(property="movimiento_id_usuario_origen", type="integer", nullable=true, description="ID del usuario que inició la acción", example=10),
 * @OA\Property(property="movimiento_id_usuario_destino", type="integer", nullable=true, description="ID del usuario receptor (si aplica)", example=12),
 * @OA\Property(property="movimiento_id_usuario_autorizado", type="integer", nullable=true, description="ID del gestor o admin que autorizó", example=1),
 * @OA\Property(property="movimiento_observaciones", type="string", nullable=true, description="Notas adicionales", example="Traslado por reestructuración de oficinas"),
 * @OA\Property(property="created_at", type="string", format="date-time"),
 * @OA\Property(property="updated_at", type="string", format="date-time"),
 * 
 * @OA\Property(
 * property="bien",
 * ref="#/components/schemas/Bien",
 * description="El bien asociado a este movimiento"
 * ),
 * @OA\Property(
 * property="departamento",
 * ref="#/components/schemas/Departamento",
 * description="Departamento destino del movimiento"
 * ),
 * @OA\Property(
 * property="usuario_origen",
 * ref="#/components/schemas/Usuario",
 * description="Datos del usuario origen (si se carga la relación)"
 * )
 * )
 */
class MovimientoBien extends Model
{
    use HasFactory;
    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'movimientos_bien';

    /**
     * La llave primaria asociada con la tabla.
     */
    protected $primaryKey = 'id'; // Corregido

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'movimiento_id_bien', // Corregido
        'movimiento_id_dep',  // Añadido
        'movimiento_fecha',
        'movimiento_tipo',
        'movimiento_cantidad',
        'movimiento_id_usuario_origen',
        'movimiento_id_usuario_destino',
        'movimiento_id_usuario_autorizado', // Corregido
        'movimiento_observaciones',
    ];

    protected $casts = [
        'movimiento_fecha' => 'datetime',
    ];

    // --- RELACIONES ---

    /**
     * Obtiene el bien asociado con el movimiento.
     */
    public function bien()
    {
        // Apunta a 'movimiento_id_bien' en esta tabla
        return $this->belongsTo(Bien::class, 'movimiento_id_bien', 'id');
    }

    /**
     * Obtiene el departamento asociado con el movimiento.
     */
    public function departamento()
    {
        // Apunta a 'movimiento_id_dep' en esta tabla
        return $this->belongsTo(Departamento::class, 'movimiento_id_dep', 'id');
    }

    /**
     * Obtiene el usuario que origina el movimiento.
     */
    public function usuarioOrigen()
    {
        return $this->belongsTo(Usuario::class, 'movimiento_id_usuario_origen', 'id');
    }

    /**
     * Obtiene el usuario que recibe el movimiento.
     */
    public function usuarioDestino()
    {
        return $this->belongsTo(Usuario::class, 'movimiento_id_usuario_destino', 'id');
    }

    /**
     * Obtiene el usuario que autorizó el movimiento.
     */
    public function usuarioAutorizado()
    {
        // Apunta a 'movimiento_id_usuario_autorizado'
        return $this->belongsTo(Usuario::class, 'movimiento_id_usuario_autorizado', 'id');
    }
}