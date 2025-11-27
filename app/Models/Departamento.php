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

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }
    public function resguardantes()
    {
        return $this->hasMany(Resguardante::class, 'res_departamento');
    }
    public function oficinas()
    {
        return $this->hasMany(Oficina::class, 'id_departamento');
    }

    public function resguardos()
    {
        return $this->hasMany(Resguardo::class, 'resguardo_id_dep');
    }
    public function movimientos()
    {
        return $this->hasMany(MovimientoBien::class, 'movimiento_id_dep');
    }
}