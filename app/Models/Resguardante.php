<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Resguardo;
use App\Models\Departamento;
use App\Models\Usuario;
use App\Models\Oficina;
use App\Models\Bien;
use OpenApi\Annotations as OA; // Importante

/**
 * @OA\Schema(
 * schema="Resguardante",
 * title="Resguardante",
 * description="Empleado o funcionario público responsable de la custodia de los bienes.",
 * required={"res_nombre", "res_apellidos", "res_departamento", "res_id_usuario"},
 * @OA\Property(property="id", type="integer", example=10, description="ID único del resguardante"),
 * @OA\Property(property="res_nombre", type="string", description="Nombre(s)", example="Juan Carlos"),
 * @OA\Property(property="res_apellidos", type="string", description="Apellidos", example="Pérez López"),
 * @OA\Property(property="res_puesto", type="string", nullable=true, description="Cargo o puesto laboral", example="Jefe de Departamento"),
 * @OA\Property(property="res_correo", type="string", format="email", nullable=true, example="juan.perez@institucion.gob.mx"),
 * @OA\Property(property="res_telefono", type="string", nullable=true, example="555-123-4567"),
 * @OA\Property(property="res_rfc", type="string", nullable=true, description="Registro Federal de Contribuyentes", example="PELJ800101XYZ"),
 * @OA\Property(property="res_curp", type="string", nullable=true, description="Clave Única de Registro de Población", example="PELJ800101HDFRRN01"),
 * @OA\Property(property="res_departamento", type="integer", description="ID del departamento al que pertenece", example=5),
 * @OA\Property(property="res_id_usuario", type="integer", description="ID del usuario de sistema (login) asociado", example=25),
 * @OA\Property(property="id_oficina", type="integer", nullable=true, description="ID de la oficina física asignada", example=3),
 * @OA\Property(property="created_at", type="string", format="date-time"),
 * @OA\Property(property="updated_at", type="string", format="date-time"),
 * 
 * @OA\Property(
 * property="departamento",
 * ref="#/components/schemas/Departamento",
 * description="Departamento administrativo (si se carga la relación)"
 * ),
 * @OA\Property(
 * property="oficina",
 * ref="#/components/schemas/Oficina",
 * description="Oficina física asignada (si se carga la relación)"
 * ),
 * @OA\Property(
 * property="usuario",
 * ref="#/components/schemas/Usuario",
 * description="Usuario de sistema asociado (si se carga la relación)"
 * ),
 * @OA\Property(
 * property="bienes",
 * type="array",
 * description="Lista de bienes bajo su resguardo actual",
 * @OA\Items(ref="#/components/schemas/Bien")
 * )
 * )
 */
class Resguardante extends Model
{
    use HasFactory;

    protected $table = 'resguardantes';

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'res_nombre',
        'res_apellidos',
        'res_puesto',
        'res_correo',
        'res_telefono',
        'res_departamento', 
        'res_id_usuario',
        'res_rfc',
        'res_curp',
        'id_oficina'
    ];

    /**
     * Obtiene el departamento al que pertenece el resguardante.
     */
    public function departamento()
    {
        // Se usa 'res_departamento' como la clave foránea
        return $this->belongsTo(Departamento::class, 'res_departamento');
    }

    /**
     * Obtiene el usuario de sistema asociado a este resguardante.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'res_id_usuario');
    }

    /**
     * Obtiene todos los resguardos (registros de asignación) de este resguardante.
     */
    public function resguardos()
    {
        return $this->hasMany(Resguardo::class, 'resguardo_id_resguardante');
    }
    /**
     * Obtiene la oficina asignada al resguardante.
     */
    public function oficina()
    {
        return $this->belongsTo(Oficina::class, 'id_oficina');
    }
    /**
     * Obtiene los bienes asignados actualmente a este resguardante.
     */
    public function bienes()
    {
        return $this->hasMany(Bien::class, 'id_resguardante');
    }
    
}