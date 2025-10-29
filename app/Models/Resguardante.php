<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resguardante extends Model
{
    use HasFactory;

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
        'res_telefono',
        'res_departamento', // <-- CORREGIDO
        'res_id_usuario',
    ];

    /**
     * Obtiene el departamento al que pertenece el resguardante.
     */
    public function departamento()
    {
        // Se usa 'res_departamento' como la clave foránea
        return $this->belongsTo(Departamento::class, 'res_departamento');
    }

    /**
     * Obtiene el usuario de sistema asociado a este resguardante.
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'res_id_usuario');
    }

    /**
     * Obtiene todos los resguardos (registros de asignación) de este resguardante.
     */
    public function resguardos()
    {
        return $this->hasMany(Resguardo::class, 'resguardo_id_resguardante');
    }
}