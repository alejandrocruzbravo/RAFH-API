<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 * schema="Gestor",
 * title="Gestor",
 * description="Perfil administrativo encargado de realizar movimientos y gestión de bienes.",
 * required={"gestor_nombre", "gestor_apellidos", "gestor_correo", "gestor_id_usuario"},
 * @OA\Property(property="id", type="integer", example=5, description="ID único del gestor"),
 * @OA\Property(property="gestor_nombre", type="string", description="Nombre(s)", example="Roberto"),
 * @OA\Property(property="gestor_apellidos", type="string", description="Apellidos", example="Martínez"),
 * @OA\Property(property="gestor_correo", type="string", format="email", description="Correo electrónico de contacto", example="roberto.mtz@empresa.com"),
 * @OA\Property(property="gestor_id_usuario", type="integer", description="ID del usuario de sistema (login) asociado", example=10),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Fecha de creación"),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Fecha de actualización"),
 * 
 * @OA\Property(
 * property="usuario",
 * ref="#/components/schemas/Usuario",
 * description="Datos de acceso al sistema (si se carga la relación)"
 * )
 * )
 */
class Gestor extends Model
{
    use HasFactory;
    protected $table = 'gestores';
    protected $fillable = [
        'gestor_nombre',
        'gestor_apellidos',
        'gestor_correo',
        'gestor_id_usuario',
    ];
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'gestor_id_usuario');
    }
}