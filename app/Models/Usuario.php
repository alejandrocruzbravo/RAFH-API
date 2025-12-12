<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OpenApi\Annotations as OA; // Importante

/**
 * @OA\Schema(
 * schema="Usuario",
 * title="Usuario (Sistema)",
 * description="Entidad de autenticación principal. Se vincula a un Rol y puede tener perfil de Gestor o Resguardante.",
 * required={"usuario_nombre", "usuario_correo", "usuario_pass", "usuario_id_rol"},
 * @OA\Property(property="id", type="integer", example=1, description="ID único del usuario"),
 * @OA\Property(property="usuario_nombre", type="string", example="Admin Principal", description="Nombre de usuario o alias"),
 * @OA\Property(property="usuario_correo", type="string", format="email", example="admin@sistema.com", description="Correo para inicio de sesión"),
 * @OA\Property(
 * property="usuario_pass", 
 * type="string", 
 * format="password", 
 * writeOnly=true, 
 * example="secret123", 
 * description="Contraseña encriptada (Solo escritura, nunca se devuelve en respuestas JSON)"
 * ),
 * @OA\Property(property="usuario_id_rol", type="integer", example=1, description="ID del Rol (1=Admin, 2=Gestor, etc.)"),
 * @OA\Property(property="email_verified_at", type="string", format="date-time", nullable=true),
 * @OA\Property(property="created_at", type="string", format="date-time"),
 * @OA\Property(property="updated_at", type="string", format="date-time"),
 * 
 * @OA\Property(
 * property="rol",
 * ref="#/components/schemas/Rol",
 * description="Rol de permisos asociado (si se carga relación)"
 * ),
 * @OA\Property(
 * property="resguardante",
 * ref="#/components/schemas/Resguardante",
 * description="Perfil de empleado resguardante asociado (si existe)"
 * ),
 * @OA\Property(
 * property="gestor",
 * ref="#/components/schemas/Gestor",
 * description="Perfil de gestor administrativo asociado (si existe)"
 * )
 * )
 */
class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory;
    // Especifica el nombre de la tabla
    protected $table = 'usuarios';
    protected $primaryKey = 'id'; 

    protected $fillable = [
        'usuario_nombre',
        'usuario_correo',
        'usuario_pass', 
        'usuario_id_rol', 
    ];

    protected $hidden = [
        'usuario_pass',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'usuario_pass' => 'hashed',
        ];
    }

    public function getAuthPassword()
    {
        return $this->usuario_pass;
    }
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'usuario_id_rol','id');
    }

    public function resguardante()
    {
        return $this->hasOne(Resguardante::class, 'res_id_usuario');
    }

    public function gestor()
    {
        return $this->hasOne(Gestor::class, 'gestor_id_usuario');
    }
}