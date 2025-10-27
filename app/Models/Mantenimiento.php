<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_bien',
        'mantenimiento_tipo',
        'mantenimiento_descripcion',
        'mantenimiento_estado',
        'fecha_programada',
        'fecha_completado',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_completado' => 'date',
    ];

    /**
     * Obtiene el bien asociado al mantenimiento.
     */
    public function bien()
    {
        return $this->belongsTo(Bien::class, 'mantenimiento_id_bien', 'id');
    }
}