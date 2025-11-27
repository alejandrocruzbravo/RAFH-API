<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Resguardo;
use App\Models\Departamento;
use App\Models\Usuario;
use App\Models\Oficina;
use App\Models\Bien;

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
        'res_apellidos',
        'res_puesto',
        'res_correo',
        'res_telefono',
        'res_departamento', 
        'res_id_usuario',
        'res_rfc',
        'res_curp',
        'id_oficina'
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
    /**
     * Obtiene la oficina asignada al resguardante.
     */
    public function oficina()
    {
        return $this->belongsTo(Oficina::class, 'id_oficina');
    }
    /**
     * Obtiene los bienes asignados actualmente a este resguardante.
     */
    public function bienes()
    {
        return $this->hasMany(Bien::class, 'id_resguardante');
    }
    
}