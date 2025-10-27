<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'departamentos';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'dep_nombre',
        'dep_descripcion',
        'dep_area_codigo', // La columna con el error de tipeo
        'dep_resposable',
        'dep_correo_institucional',
    ];
}