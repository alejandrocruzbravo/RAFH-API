<?php


namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <-- ¡Importante!

class usuario extends Authenticatable
{
    use HasApiTokens, Notifiable;

    // Especifica el nombre de la tabla si no sigue la convención de Laravel
    protected $table = 'usuarios';

    // Especifica la clave primaria si no es 'id'
    protected $primaryKey = 'id';

    protected $fillable = [
        'usuario_nombre',
        'usuario_correo',
        'usuario_pass', // Laravel buscará este campo para la autenticación
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
        return $this->usuario_id_rol === 1;
    }

    /**
     * Sobrescribimos el nombre de la columna de la contraseña.
     */
    public function getAuthPassword()
    {
        return $this->usuario_pass;
    }
}
?>