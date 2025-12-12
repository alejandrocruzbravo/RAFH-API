<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA; 

/**
 * @OA\Schema(
 * schema="Departamento",
 * title="Departamento",
 * description="Unidad administrativa que pertenece a un Área y contiene Oficinas.",
 * required={"dep_nombre", "dep_codigo", "id_area"},
 * @OA\Property(property="id", type="integer", example=10, description="ID único del departamento"),
 * @OA\Property(property="dep_nombre", type="string", description="Nombre del departamento", example="Recursos Humanos"),
 * @OA\Property(property="dep_codigo", type="string", description="Clave o código interno", example="RH-001"),
 * @OA\Property(property="dep_resposable", type="string", description="Nombre del responsable del departamento", example="Lic. María González"),
 * @OA\Property(property="dep_correo_institucional", type="string", format="email", description="Correo de contacto oficial", example="rh@institucion.gob.mx"),
 * @OA\Property(property="id_area", type="integer", description="ID del Área superior", example=2),
 * @OA\Property(property="created_at", type="string", format="date-time"),
 * @OA\Property(property="updated_at", type="string", format="date-time"),
 * 
 * @OA\Property(
 * property="area",
 * ref="#/components/schemas/Area",
 * description="Objeto del Área a la que pertenece (si se carga con with)"
 * )
 * )
 */
class Departamento extends Model
{
    use HasFactory;

    protected $table = 'departamentos';

    protected $fillable = [
        'dep_nombre',
        'dep_codigo',
        'dep_resposable',
        'dep_correo_institucional',
        'id_area',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }
    public function resguardantes()
    {
        return $this->hasMany(Resguardante::class, 'res_departamento');
    }
    public function oficinas()
    {
        return $this->hasMany(Oficina::class, 'id_departamento');
    }

    public function resguardos()
    {
        return $this->hasMany(Resguardo::class, 'resguardo_id_dep');
    }
    public function movimientos()
    {
        return $this->hasMany(MovimientoBien::class, 'movimiento_id_dep');
    }
}