<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionInventario extends Model
{
    use HasFactory;

    protected $fillable = [
        'institucion_id',
        'configuracion_json'
    ];

    // Esto hace la magia: Convierte el JSON de MySQL a Array de PHP automáticamente
    protected $casts = [
        'configuracion_json' => 'array',
    ];
}