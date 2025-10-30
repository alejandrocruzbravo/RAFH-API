<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gestor extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'gestores';

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'gestor_nombre',
        'gestor_apellidos',
        'gestor_correo',
        'gestor_id_usuario',
    ];
    /**
     * Obtiene el usuario de sistema asociado.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'gestor_id_usuario');
    }
}