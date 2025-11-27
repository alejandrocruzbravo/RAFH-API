<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;
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

    /**
     * Sobrescribimos el nombre de la columna de la contraseña.
     */
    public function getAuthPassword()
    {
        return $this->usuario_pass;
    }
    public function rol()
    {
        // Basado en tu diagrama, la llave foránea es 'usuario_id_rol'
        return $this->belongsTo(Rol::class, 'usuario_id_rol','id');
    }
    // En app/Models/Usuario.php

    public function resguardante()
    {
        // Asumiendo que esta es la FK en la tabla resguardantes
        return $this->hasOne(Resguardante::class, 'res_id_usuario');
    }

    public function gestor()
    {
        // Asumiendo que esta es la FK en la tabla gestores
        return $this->hasOne(Gestor::class, 'gestor_id_usuario');
    }
}