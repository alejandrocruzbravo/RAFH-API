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
        'gestor_apellido1',
        'gestor_apellido2',
        'gestor_puesto',
        'gestor_correo',
        'gestor_departamento',
        'gestor_telefono',
        'gestor_id_usuario',
    ];
}