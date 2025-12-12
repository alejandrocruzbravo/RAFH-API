<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA; // Importante

/**
 * @OA\Schema(
 * schema="Rol",
 * title="Rol de Usuario",
 * description="Define el nivel de acceso y permisos dentro del sistema (ej. Administrador, Gestor, Resguardante).",
 * required={"rol_nombre"},
 * @OA\Property(property="id", type="integer", example=1, description="ID único del rol"),
 * @OA\Property(property="rol_nombre", type="string", description="Nombre del rol", example="Administrador"),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Fecha de creación"),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Fecha de actualización"),
 * 
 * @OA\Property(
 * property="usuarios",
 * type="array",
 * description="Lista de usuarios que poseen este rol (si se carga la relación)",
 * @OA\Items(ref="#/components/schemas/Usuario")
 * )
 * )
 */
class Rol extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'roles';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'rol_nombre',
    ];

    /**
     * Obtiene los usuarios que tienen este rol.
     */
    public function usuarios()
    {
        // Un rol puede tener muchos usuarios
        // La clave foránea en la tabla 'usuarios' es 'usuario_id_rol'
        return $this->hasMany(Usuario::class, 'usuario_id_rol');
    }
}