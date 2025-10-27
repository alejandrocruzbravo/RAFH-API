<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resguardante extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'resguardantes';

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'res_nombre',
        'res_apellido1',
        'res_apellido2',
        'res_puesto',
        'res_correo',
        'res_departamento',
        'res_telefono',
        'res_id_usuario',
    ];
}