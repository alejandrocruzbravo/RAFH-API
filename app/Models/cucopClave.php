<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class cucopClave extends Model
{
    protected $table = 'catalogo_cucop';

    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'cucop_plus',
        'cucop',
        'descripcion',
        'partida_especifica',
        'descripcion_partida_especifia',
        'partida_generica',
        'descripcion_partida_generica',
        'concepto',
        'descripcion_concepto',
        'capitulo',
        'fecha_alta_cucop'];

    

    
}
