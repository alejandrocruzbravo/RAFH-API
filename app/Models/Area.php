<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Departamento;
use App\Models\Edificio;
use App\Models\Resguardante;
use App\Models\Oficina;
/**
 * @OA\Schema(
 * schema="Area",
 * title="Area",
 * description="Modelo de Área",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="area_nombre", type="string", example="Recursos Humanos"),
 * @OA\Property(property="area_codigo", type="string", example="RH-001"),
 * @OA\Property(property="created_at", type="string", format="date-time"),
 * @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Area extends Model
{
    use HasFactory;

    protected $table = 'areas';

    protected $fillable = [
        'area_nombre',
        'area_codigo',
        //'id_resguardante_responsable',
        'id_edificio',
    ];

    public function departamentos()
    {
        return $this->hasMany(Departamento::class, 'id_area');
    }

    public function edificio() 
    {
        return $this->belongsTo(Edificio::class, 'id_edificio');
    }

    /*public function responsable() 
    {
        return $this->belongsTo(Resguardante::class, 'id_resguardante_responsable');
    }*/

        
    /**
     * Obtiene todas las oficinas que pertenecen a esta área
     * a través de sus departamentos.
     * (Área -> tiene muchos Departamentos -> tienen muchas Oficinas)
     */
    public function oficinas()
    {
        return $this->hasManyThrough(
            Oficina::class,      // El modelo final que queremos (Oficina)
            Departamento::class, // El modelo intermedio (Departamento)
            'id_area',           // FK en tabla intermedia (departamentos.id_area)
            'id_departamento',   // FK en tabla final (oficinas.id_departamento)
            'id',                // PK local (areas.id)
            'id'                 // PK intermedia (departamentos.id)
        );
    }
}