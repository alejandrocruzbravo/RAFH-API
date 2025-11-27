<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Edificio extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'edificios';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'nombre',
    ];

    /**
     * Obtiene todas las áreas asociadas a este edificio.
     * * Basado en el diagrama de 'areas' que tiene 'id_edificio'.
     */
    public function areas()
    {
        return $this->hasMany(Area::class, 'id_edificio');
    }
    public function oficinas()
    {
        // Agregado: 'id_edificio' también existe en la tabla 'oficinas'
        return $this->hasMany(Oficina::class, 'id_edificio');
    }
}