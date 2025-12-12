<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA; // Importante

/**
 * @OA\Schema(
 * schema="Oficina",
 * title="Oficina",
 * description="Espacio físico específico (ej. Sala de Juntas, Despacho 101) perteneciente a un departamento y ubicado en un edificio.",
 * required={"id_departamento", "id_edificio", "ofi_codigo", "nombre"},
 * @OA\Property(property="id", type="integer", example=20, description="ID único de la oficina"),
 * @OA\Property(property="id_departamento", type="integer", description="ID del departamento administrativo al que pertenece", example=5),
 * @OA\Property(property="id_edificio", type="integer", description="ID del edificio físico donde se ubica", example=2),
 * @OA\Property(property="ofi_codigo", type="string", description="Código interno único", example="ADM-101"),
 * @OA\Property(property="nombre", type="string", description="Nombre de la oficina o espacio", example="Gerencia General"),
 * @OA\Property(property="referencia", type="string", nullable=true, description="Detalles de ubicación local", example="Segundo piso, puerta de cristal"),
 * @OA\Property(property="created_at", type="string", format="date-time"),
 * @OA\Property(property="updated_at", type="string", format="date-time"),
 * 
 * @OA\Property(
 * property="edificio",
 * ref="#/components/schemas/Edificio",
 * description="Datos del edificio (si se carga la relación)"
 * ),
 * @OA\Property(
 * property="departamento",
 * ref="#/components/schemas/Departamento",
 * description="Datos del departamento (si se carga la relación)"
 * ),
 * @OA\Property(
 * property="bienes",
 * type="array",
 * description="Lista de bienes ubicados actualmente aquí",
 * @OA\Items(ref="#/components/schemas/Bien")
 * )
 * )
 */
class Oficina extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     */
    protected $table = 'oficinas';

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'id_departamento',
        'id_edificio',
        'ofi_codigo',
        'nombre',
        'referencia',
    ];

    /**
     * Define la relación: una oficina pertenece a un edificio.
     */
    public function edificio()
    {
        return $this->belongsTo(Edificio::class, 'id_edificio');
    }
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'id_departamento');
    }
    public function bienes()
    {
        return $this->hasMany(Bien::class, 'id_oficina');
    }
    public function resguardantes()
    {
        return $this->hasMany(Resguardante::class, 'id_oficina');
    }
}