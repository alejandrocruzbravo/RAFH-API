<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA; // Importante

/**
 * @OA\Schema(
 * schema="Resguardo",
 * title="Resguardo (Asignación)",
 * description="Registro que vincula un bien con un resguardante en una fecha específica.",
 * required={"resguardo_id_bien", "resguardo_id_resguardante", "resguardo_fecha_asignacion"},
 * @OA\Property(property="id", type="integer", example=1, description="ID del registro de resguardo"),
 * @OA\Property(property="resguardo_id_bien", type="integer", description="ID del bien asignado", example=55),
 * @OA\Property(property="resguardo_id_resguardante", type="integer", description="ID del empleado responsable", example=10),
 * @OA\Property(property="resguardo_id_dep", type="integer", nullable=true, description="ID del departamento donde se encontraba al asignarse", example=3),
 * @OA\Property(property="resguardo_fecha_asignacion", type="string", format="date-time", description="Fecha en que se firmó/asignó el resguardo"),
 * @OA\Property(property="created_at", type="string", format="date-time"),
 * @OA\Property(property="updated_at", type="string", format="date-time"),
 * 
 * @OA\Property(
 * property="bien",
 * ref="#/components/schemas/Bien",
 * description="El bien objeto del resguardo (si se carga relación)"
 * ),
 * @OA\Property(
 * property="resguardante",
 * ref="#/components/schemas/Resguardante",
 * description="La persona responsable (si se carga relación)"
 * ),
 * @OA\Property(
 * property="departamento",
 * ref="#/components/schemas/Departamento",
 * description="Departamento asociado al resguardo (si se carga relación)"
 * )
 * )
 */
class Resguardo extends Model
{
    use HasFactory;

    protected $table = 'resguardos';

    protected $fillable = [
        'resguardo_id_bien',
        'resguardo_id_resguardante',
        'resguardo_fecha_asignacion',
        'resguardo_id_dep',
    ];

    protected $casts = [
        'resguardo_fecha_asignacion' => 'datetime',
    ];

    // --- RELACIONES ---

    public function bien()
    {
        return $this->belongsTo(Bien::class, 'resguardo_id_bien');
    }

    public function resguardante()
    {
        return $this->belongsTo(Resguardante::class, 'resguardo_id_resguardante');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'resguardo_id_dep');
    }
}