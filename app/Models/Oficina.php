<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oficina extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'oficinas';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'id_departamento',
        'id_edificio',
        'ofi_codigo',
        'nombre',
        'referencia',
    ];

    /**
     * Define la relación: una oficina pertenece a un edificio.
     */
    public function edificio()
    {
        return $this->belongsTo(Edificio::class, 'id_edificio');
    }
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'id_departamento');
    }
    public function bienes()
    {
        return $this->hasMany(Bien::class, 'id_oficina');
    }
    public function resguardantes()
    {
        return $this->hasMany(Resguardante::class, 'id_oficina');
    }
}