<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Annotations as OA; 

/**
 * @OA\Schema(
 * schema="CatalogoCambCucop",
 * title="Catálogo CAMB - CUCOP",
 * description="Catálogo de claves que relaciona el CAMB con la partida específica y el CUCOP.",
 * required={"clave_cucop", "partida_especifica", "descripcion", "camb"},
 * @OA\Property(property="id", type="integer", example=1, description="ID autoincremental"),
 * @OA\Property(property="clave_cucop", type="string", description="Clave Única de las Contrataciones Públicas", example="21100001"),
 * @OA\Property(property="partida_especifica", type="string", description="Partida presupuestal específica", example="21101"),
 * @OA\Property(property="descripcion", type="string", description="Descripción del bien o servicio en el catálogo", example="Materiales y útiles de oficina"),
 * @OA\Property(property="camb", type="string", description="Clave de Administración de Bienes (CAMB)", example="I51101-0001"),
 * @OA\Property(property="created_at", type="string", format="date-time", description="Fecha de creación"),
 * @OA\Property(property="updated_at", type="string", format="date-time", description="Fecha de última actualización")
 * )
 */
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