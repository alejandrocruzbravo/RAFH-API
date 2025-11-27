<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * Obtiene los permisos asociados a este rol.
     */
    // public function permisos()
    // {
    //     // Relación muchos a muchos (basado en tu diagrama)
    //     // Modelo, tabla_pivote, fk_propia, fk_relacionada
    //     return $this->belongsToMany(
    //         Permiso::class,      // Asumiendo que el modelo se llama 'Permiso'
    //         'roles_permisos',   // Tabla pivote
    //         'id_rol',           // Clave foránea de Rol en la pivote
    //         'id_permiso'        // Clave foránea de Permiso en la pivote
    //     );
    // }
}