<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resguardo extends Model
{
    use HasFactory;

    protected $table = 'resguardos';

    protected $fillable = [
        'resguardo_id_bien',
        'resguardo_id_resguardante',
        'resguardo_fecha_asignacion',
        'resguardo_id_dep',
    ];

    protected $casts = [
        'resguardo_fecha_asignacion' => 'datetime',
    ];

    // --- RELACIONES ---

    public function bien()
    {
        return $this->belongsTo(Bien::class, 'resguardo_id_bien');
    }

    public function resguardante()
    {
        return $this->belongsTo(Resguardante::class, 'resguardo_id_resguardante');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'resguardo_id_dep');
    }
}