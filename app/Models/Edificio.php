<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA; 

/**
 * @OA\Schema(
 * schema="Edificio",
 * title="Edificio",
 * description="Ubicación física principal (estructura) que agrupa Áreas y Oficinas.",
 * required={"nombre"},
 * @OA\Property(property="id", type="integer", example=1, description="ID único del edificio"),
 * @OA\Property(property="nombre", type="string", description="Nombre o identificación del edificio", example="Torre Administrativa A"),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Fecha de creación"),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Fecha de última actualización"),
 * 
 * @OA\Property(
 * property="areas",
 * type="array",
 * description="Lista de áreas asociadas (si se carga la relación)",
 * @OA\Items(ref="#/components/schemas/Area")
 * ),
 * @OA\Property(
 * property="oficinas",
 * type="array",
 * description="Lista de oficinas físicas en este edificio (si se carga la relación)",
 * @OA\Items(ref="#/components/schemas/Oficina")
 * )
 * )
 */
class Edificio extends Model
{
    use HasFactory;
    protected $table = 'edificios';
    protected $fillable = [
        'nombre',
    ];

    public function areas()
    {
        return $this->hasMany(Area::class, 'id_edificio');
    }
    public function oficinas()
    {
        return $this->hasMany(Oficina::class, 'id_edificio');
    }
}