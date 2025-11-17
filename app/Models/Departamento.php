<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    protected $table = 'departamentos';

    protected $fillable = [
        'dep_nombre',
        'dep_codigo',
        'dep_resposable',
        'dep_correo_institucional',
        'id_area',
    ];

    /**
     * Obtiene el área a la que pertenece el departamento.
     */
    public function area()
    {
        return $this->belongsTo(Area::class, 'id');
    }

    /**
     * Obtiene los resguardantes que pertenecen a este departamento.
     */
    public function resguardantes()
    {
        // --- CORREGIDO ---
        // Se usa 'res_departamento' como la clave foránea
        return $this->hasMany(Resguardante::class, 'res_departamento');
    }
    
    public function oficinas()
    {
        return $this->hasMany(Oficina::class, 'id_departamento');
    }
}