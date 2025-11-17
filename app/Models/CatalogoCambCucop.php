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
        'tipo',
        'clave_cucop',
        'partida_especifica',
        'clave_cucop_plus',
        'descripcion',
        'nivel',
        'camb',
        'unidad_medida',
        'tipo_contratacion',
    ];
}