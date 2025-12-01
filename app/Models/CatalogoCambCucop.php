<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatalogoCambCucop extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'catalogo_camb_cucop';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'clave_cucop',
        'partida_especifica',
        'descripcion',
        'camb',
    ];
}