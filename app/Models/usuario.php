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

    public function isAdmin(){
        return $this->usuario_id_rol === 1; // <-- ID del rol Administrador
    }

    /**
     * Sobrescribimos el nombre de la columna de la contraseña.
     */
    public function getAuthPassword()
    {
        return $this->usuario_pass;
    }
}