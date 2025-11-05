<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'areas';

    /**
     * Los atributos que se pueden asignar masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'area_nombre',
        'area_codigo',
        'id_resguardante_responsable',
        'id_edificio',                 
    ];
    public function departamentos()
    {
        return $this->hasMany(Departamento::class, 'id_area');
    }
    public function edificio() {
        return $this->belongsTo(Edificio::class, 'id_edificio');
    }
    public function responsable() {
        return $this->belongsTo(Resguardante::class, 'id_resguardante_responsable', 'id');
    }
}